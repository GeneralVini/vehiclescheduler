#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

CODEX_DIR="${CODEX_HOME:-$HOME/.codex}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CHATS_DIR="$SCRIPT_DIR/chats"

HOST="$(hostname 2>/dev/null || echo linux)"
TS="$(date +%Y%m%d-%H%M%S)"

die() {
  echo "ERRO: $*" >&2
  exit 1
}

info() {
  echo "[codex-backup] $*"
}

usage() {
  cat <<EOF
Uso:
  $0 backup
      Salva apenas chats/sessões de ~/.codex para ./chats.

  $0 restore
      Restaura chats/sessões de ./chats para ~/.codex.
      Só copia o que for mais novo em ./chats.

  $0 backup-full
      Cria um backup completo .tar.gz de ~/.codex dentro de ./chats.
      Pode incluir config, credenciais, tokens e autenticação.

  $0 restore-full [arquivo.tar.gz]
      Restaura um backup completo .tar.gz para ~/.codex.
      Se não informar o arquivo, usa o codex-full-*.tar.gz mais recente em ./chats.
      Só copia o que for mais novo no backup.

  $0 restore-full-force [arquivo.tar.gz]
      Igual ao restore-full, mas sobrescreve tudo sem checar data.

  $0 list
      Lista os backups em ./chats.

  $0 where
      Mostra os caminhos usados.

Diretórios:
  CODEX_DIR: $CODEX_DIR
  CHATS_DIR: $CHATS_DIR

Exemplos:
  $0 backup
  $0 restore
  $0 backup-full
  $0 restore-full ./chats/codex-full-gondor-20260525-213659.tar.gz
  $0 restore-full
EOF
}

require_codex_dir() {
  [[ -d "$CODEX_DIR" ]] || die "Diretório do Codex não encontrado: $CODEX_DIR"
}

require_chats_dir() {
  [[ -d "$CHATS_DIR" ]] || die "Diretório ./chats não encontrado: $CHATS_DIR"
}

copy_newer_item() {
  local src="$1"
  local dst="$2"

  [[ -e "$src" ]] || return 0

  if command -v rsync >/dev/null 2>&1; then
    if [[ -d "$src" ]]; then
      mkdir -p "$dst"
      rsync -a --update --itemize-changes "$src/" "$dst/"
    else
      mkdir -p "$(dirname "$dst")"
      rsync -a --update --itemize-changes "$src" "$(dirname "$dst")/"
    fi
  else
    if [[ -d "$src" ]]; then
      mkdir -p "$dst"
      cp -au "$src/." "$dst/"
    else
      mkdir -p "$(dirname "$dst")"
      cp -au "$src" "$(dirname "$dst")/"
    fi
  fi
}

copy_force_item() {
  local src="$1"
  local dst="$2"

  [[ -e "$src" ]] || return 0

  if command -v rsync >/dev/null 2>&1; then
    if [[ -d "$src" ]]; then
      mkdir -p "$dst"
      rsync -a --itemize-changes "$src/" "$dst/"
    else
      mkdir -p "$(dirname "$dst")"
      rsync -a --itemize-changes "$src" "$(dirname "$dst")/"
    fi
  else
    if [[ -d "$src" ]]; then
      mkdir -p "$dst"
      cp -a "$src/." "$dst/"
    else
      mkdir -p "$(dirname "$dst")"
      cp -a "$src" "$(dirname "$dst")/"
    fi
  fi
}

latest_full_archive() {
  require_chats_dir

  local latest
  latest="$(find "$CHATS_DIR" -maxdepth 1 -type f -name 'codex-full-*.tar.gz' -printf '%T@ %p\n' 2>/dev/null \
    | sort -nr \
    | head -n 1 \
    | cut -d' ' -f2-)"

  [[ -n "${latest:-}" ]] || die "Nenhum codex-full-*.tar.gz encontrado em ./chats."

  echo "$latest"
}

make_safety_archive_before_restore() {
  [[ -d "$CODEX_DIR" ]] || return 0

  mkdir -p "$CHATS_DIR"

  local safety="$CHATS_DIR/codex-current-before-restore-$HOST-$TS.tar.gz"

  info "Criando backup de segurança do estado atual:"
  info "$safety"

  local tmp
  tmp="$(mktemp -d)"

  mkdir -p "$tmp/codex-home"
  cp -a "$CODEX_DIR/." "$tmp/codex-home/"

  cat > "$tmp/MANIFEST.txt" <<EOF
Backup de segurança antes de restore
Data: $(date -Is)
Host: $HOST
Origem: $CODEX_DIR
Tipo: safety-before-restore
EOF

  tar -C "$tmp" -czf "$safety" .
  rm -rf "$tmp"
}

backup_chats() {
  require_codex_dir
  mkdir -p "$CHATS_DIR"

  info "Backup de chats/sessões"
  info "Origem:  $CODEX_DIR"
  info "Destino: $CHATS_DIR"
  info "Regra: origem mais nova vence."

  local found=0

  for item in sessions session history history.jsonl; do
    if [[ -e "$CODEX_DIR/$item" ]]; then
      copy_newer_item "$CODEX_DIR/$item" "$CHATS_DIR/$item"
      found=1
    fi
  done

  while IFS= read -r -d '' file; do
    local base
    base="$(basename "$file")"
    copy_newer_item "$file" "$CHATS_DIR/$base"
    found=1
  done < <(
    find "$CODEX_DIR" -maxdepth 1 -type f \
      \( -name "*.jsonl" -o -iname "*rollout*" -o -iname "*session*" -o -iname "history*" \) \
      -print0 2>/dev/null
  )

  [[ "$found" -eq 1 ]] || die "Nenhum item de chat/sessão encontrado em $CODEX_DIR"

  cat > "$CHATS_DIR/.BACKUP-MANIFEST.txt" <<EOF
Backup/espelho de chats do Codex
Última execução: $(date -Is)
Host: $HOST
Usuário: ${USER:-unknown}
Origem: $CODEX_DIR
Destino: $CHATS_DIR
Regra: arquivos mais novos prevalecem
EOF

  info "Backup de chats concluído."
}

restore_chats() {
  require_chats_dir
  mkdir -p "$CODEX_DIR"

  make_safety_archive_before_restore

  info "Restore de chats/sessões"
  info "Origem:  $CHATS_DIR"
  info "Destino: $CODEX_DIR"
  info "Regra: ./chats mais novo vence."

  shopt -s nullglob dotglob

  local copied_any=0

  for src in "$CHATS_DIR"/* "$CHATS_DIR"/.[!.]* "$CHATS_DIR"/..?*; do
    local base
    base="$(basename "$src")"

    case "$base" in
      ".BACKUP-MANIFEST.txt")
        continue
        ;;
      codex-full-*.tar.gz|codex-current-before-restore-*.tar.gz|*.tgz|*.zip)
        continue
        ;;
    esac

    copy_newer_item "$src" "$CODEX_DIR/$base"
    copied_any=1
  done

  shopt -u nullglob dotglob

  [[ "$copied_any" -eq 1 ]] || die "Nenhum arquivo válido de chat encontrado em ./chats."

  info "Restore de chats concluído."
}

backup_full() {
  require_codex_dir
  mkdir -p "$CHATS_DIR"

  local out="$CHATS_DIR/codex-full-$HOST-$TS.tar.gz"

  info "Backup FULL do Codex"
  info "Origem:  $CODEX_DIR"
  info "Destino: $out"
  info "ATENÇÃO: pode incluir config, autenticação, tokens e credenciais."

  local tmp
  tmp="$(mktemp -d)"

  mkdir -p "$tmp/codex-home"
  cp -a "$CODEX_DIR/." "$tmp/codex-home/"

  cat > "$tmp/MANIFEST.txt" <<EOF
Backup FULL do Codex
Data: $(date -Is)
Host: $HOST
Usuário: ${USER:-unknown}
Origem: $CODEX_DIR
Tipo: full
ATENÇÃO: pode conter autenticação, tokens e configuração sensível.
EOF

  tar -C "$tmp" -czf "$out" .
  rm -rf "$tmp"

  info "Backup FULL criado:"
  echo "$out"
}

restore_full_archive() {
  local archive="${1:-}"
  local force="${2:-no}"

  if [[ -z "$archive" ]]; then
    archive="$(latest_full_archive)"
  fi

  [[ -f "$archive" ]] || die "Arquivo não encontrado: $archive"

  mkdir -p "$CODEX_DIR" "$CHATS_DIR"

  make_safety_archive_before_restore

  info "Restore FULL do Codex"
  info "Arquivo: $archive"
  info "Destino: $CODEX_DIR"

  if [[ "$force" == "yes" ]]; then
    info "Regra: FORCE, sobrescrevendo sem checar data."
  else
    info "Regra: backup mais novo vence."
  fi

  local tmp
  tmp="$(mktemp -d)"

  tar -C "$tmp" -xzf "$archive"

  local extracted_home=""

  if [[ -d "$tmp/codex-home" ]]; then
    extracted_home="$tmp/codex-home"
  elif [[ -d "$tmp/.codex" ]]; then
    extracted_home="$tmp/.codex"
  else
    # fallback: se o tar tiver sido feito com o conteúdo direto
    extracted_home="$tmp"
  fi

  if [[ "$force" == "yes" ]]; then
    copy_force_item "$extracted_home" "$CODEX_DIR"
  else
    copy_newer_item "$extracted_home" "$CODEX_DIR"
  fi

  rm -rf "$tmp"

  info "Restore FULL concluído."
}

list_all() {
  mkdir -p "$CHATS_DIR"

  info "Arquivos em:"
  info "$CHATS_DIR"
  echo

  find "$CHATS_DIR" -maxdepth 2 -type f -print | sort
}

show_where() {
  echo "CODEX_DIR=$CODEX_DIR"
  echo "CHATS_DIR=$CHATS_DIR"
  echo

  if [[ -d "$CODEX_DIR" ]]; then
    echo "Arquivos prováveis de chat/sessão no CODEX_DIR:"
    find "$CODEX_DIR" -maxdepth 4 \
      \( -type d -name "sessions" -o -type f -name "*.jsonl" -o -type f -iname "*session*" -o -type f -iname "*rollout*" -o -type f -iname "history*" \) \
      -print | sort | head -100
  else
    echo "CODEX_DIR ainda não existe."
  fi

  echo

  if [[ -d "$CHATS_DIR" ]]; then
    echo "Backups FULL em ./chats:"
    find "$CHATS_DIR" -maxdepth 1 -type f -name 'codex-full-*.tar.gz' -print | sort
    echo
    echo "Backup FULL mais recente detectado:"
    latest_full_archive 2>/dev/null || true
  else
    echo "./chats ainda não existe."
  fi
}

cmd="${1:-}"
shift || true

case "$cmd" in
  backup)
    backup_chats
    ;;

  restore)
    # Se passar .tar.gz para restore, ele entende como restore-full.
    if [[ "${1:-}" == *.tar.gz ]]; then
      restore_full_archive "$1" "no"
    else
      restore_chats
    fi
    ;;

  backup-full)
    backup_full
    ;;

  restore-full)
    restore_full_archive "${1:-}" "no"
    ;;

  restore-full-force)
    restore_full_archive "${1:-}" "yes"
    ;;

  list)
    list_all
    ;;

  where)
    show_where
    ;;

  -h|--help|help|"")
    usage
    ;;

  *)
    usage
    die "Comando inválido: $cmd"
    ;;
esac