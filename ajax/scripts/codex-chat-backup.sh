#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

# Diretório real do Codex no Linux.
# Padrão: ~/.codex
# Pode sobrescrever assim:
# CODEX_HOME=/outro/caminho/.codex ./codex-chat-backup.sh backup
CODEX_DIR="${CODEX_HOME:-$HOME/.codex}"

# Diretório onde este script está salvo
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Espelho apenas dos chats/sessões
CHATS_DIR="$SCRIPT_DIR/chats"

# Espelho completo do CODEX_HOME, incluindo config e possível autenticação
FULL_DIR="$SCRIPT_DIR/chats-full"
FULL_HOME_DIR="$FULL_DIR/codex-home"

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
      Copia chats/sessões de ~/.codex para ./chats.
      Só atualiza em ./chats o que for mais novo na origem.

  $0 restore
      Copia chats/sessões de ./chats para ~/.codex.
      Só atualiza em ~/.codex o que for mais novo em ./chats.

  $0 restore-force
      Copia tudo de ./chats para ~/.codex, sobrescrevendo sem checar data.

  $0 backup-full
      Copia TODO o CODEX_HOME para ./chats-full/codex-home.
      ATENÇÃO: pode incluir config, autenticação, tokens e credenciais.
      Só atualiza em ./chats-full o que for mais novo na origem.

  $0 restore-full
      Copia TODO o ./chats-full/codex-home para ~/.codex.
      ATENÇÃO: pode restaurar config, autenticação, tokens e credenciais.
      Só atualiza em ~/.codex o que for mais novo em ./chats-full.

  $0 restore-full-force
      Copia TODO o ./chats-full/codex-home para ~/.codex, sobrescrevendo sem checar data.

  $0 list
      Lista arquivos em ./chats e ./chats-full.

  $0 where
      Mostra caminhos usados e arquivos detectados.

Diretórios:
  CODEX_DIR:     $CODEX_DIR
  CHATS_DIR:     $CHATS_DIR
  FULL_HOME_DIR: $FULL_HOME_DIR

Variável opcional:
  CODEX_HOME=/outro/caminho/.codex

Exemplos:
  $0 backup
  $0 restore
  $0 backup-full
  $0 restore-full
EOF
}

require_codex_dir() {
  [[ -d "$CODEX_DIR" ]] || die "Diretório do Codex não encontrado: $CODEX_DIR"
}

require_chats_dir() {
  [[ -d "$CHATS_DIR" ]] || die "Diretório ./chats não encontrado: $CHATS_DIR"
}

require_full_dir() {
  [[ -d "$FULL_HOME_DIR" ]] || die "Diretório de backup full não encontrado: $FULL_HOME_DIR"
}

prevent_recursive_full_backup() {
  require_codex_dir
  mkdir -p "$FULL_DIR"

  local src_real
  local dst_real

  src_real="$(cd "$CODEX_DIR" && pwd -P)"
  dst_real="$(cd "$FULL_DIR" && pwd -P)"

  case "$dst_real/" in
    "$src_real"/*)
      die "O backup full está dentro do CODEX_DIR. Mova o script para fora de ~/.codex para evitar cópia recursiva."
      ;;
  esac
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

make_current_safety_backup_chats() {
  [[ -d "$CODEX_DIR" ]] || return 0

  local safety_dir="$CHATS_DIR/.restore-safety/before-restore-$HOST-$TS"
  mkdir -p "$safety_dir"

  info "Criando backup de segurança dos chats atuais em:"
  info "$safety_dir"

  for item in sessions session history history.jsonl; do
    if [[ -e "$CODEX_DIR/$item" ]]; then
      copy_force_item "$CODEX_DIR/$item" "$safety_dir/$item"
    fi
  done

  find "$CODEX_DIR" -maxdepth 1 -type f \
    \( -name "*.jsonl" -o -iname "*rollout*" -o -iname "*session*" -o -iname "history*" \) \
    -print0 2>/dev/null |
  while IFS= read -r -d '' file; do
    local base
    base="$(basename "$file")"
    copy_force_item "$file" "$safety_dir/$base"
  done
}

make_current_safety_backup_full() {
  [[ -d "$CODEX_DIR" ]] || return 0

  prevent_recursive_full_backup

  local safety_dir="$FULL_DIR/.restore-safety/before-full-restore-$HOST-$TS"
  mkdir -p "$safety_dir"

  info "Criando backup FULL de segurança do estado atual em:"
  info "$safety_dir/codex-home-current"

  copy_force_item "$CODEX_DIR" "$safety_dir/codex-home-current"
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

  if [[ "$found" -eq 0 ]]; then
    die "Nenhum item de chat/sessão encontrado em $CODEX_DIR"
  fi

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

  make_current_safety_backup_chats

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
      ".restore-safety"|".BACKUP-MANIFEST.txt")
        continue
        ;;
      *.tar.gz|*.tgz|*.zip)
        continue
        ;;
    esac

    copy_newer_item "$src" "$CODEX_DIR/$base"
    copied_any=1
  done

  shopt -u nullglob dotglob

  [[ "$copied_any" -eq 1 ]] || die "Nenhum arquivo válido encontrado em ./chats."

  info "Restore de chats concluído."
}

restore_chats_force() {
  require_chats_dir
  mkdir -p "$CODEX_DIR"

  make_current_safety_backup_chats

  info "RESTORE FORCE de chats/sessões"
  info "Origem:  $CHATS_DIR"
  info "Destino: $CODEX_DIR"
  info "Regra: sobrescrever sem checar data."

  shopt -s nullglob dotglob

  local copied_any=0

  for src in "$CHATS_DIR"/* "$CHATS_DIR"/.[!.]* "$CHATS_DIR"/..?*; do
    local base
    base="$(basename "$src")"

    case "$base" in
      ".restore-safety"|".BACKUP-MANIFEST.txt")
        continue
        ;;
      *.tar.gz|*.tgz|*.zip)
        continue
        ;;
    esac

    copy_force_item "$src" "$CODEX_DIR/$base"
    copied_any=1
  done

  shopt -u nullglob dotglob

  [[ "$copied_any" -eq 1 ]] || die "Nenhum arquivo válido encontrado em ./chats."

  info "Restore force de chats concluído."
}

backup_full() {
  require_codex_dir
  prevent_recursive_full_backup
  mkdir -p "$FULL_HOME_DIR"

  info "Backup FULL do Codex"
  info "Origem:  $CODEX_DIR"
  info "Destino: $FULL_HOME_DIR"
  info "ATENÇÃO: pode incluir config, autenticação, tokens e credenciais."
  info "Regra: origem mais nova vence."

  copy_newer_item "$CODEX_DIR" "$FULL_HOME_DIR"

  cat > "$FULL_DIR/.BACKUP-FULL-MANIFEST.txt" <<EOF
Backup FULL do Codex
Última execução: $(date -Is)
Host: $HOST
Usuário: ${USER:-unknown}
Origem: $CODEX_DIR
Destino: $FULL_HOME_DIR
Regra: arquivos mais novos prevalecem
ATENÇÃO: pode conter autenticação, tokens e configuração sensível.
EOF

  info "Backup FULL concluído."
}

restore_full() {
  require_full_dir
  mkdir -p "$CODEX_DIR"

  make_current_safety_backup_full

  info "Restore FULL do Codex"
  info "Origem:  $FULL_HOME_DIR"
  info "Destino: $CODEX_DIR"
  info "ATENÇÃO: pode restaurar config, autenticação, tokens e credenciais."
  info "Regra: ./chats-full mais novo vence."

  copy_newer_item "$FULL_HOME_DIR" "$CODEX_DIR"

  info "Restore FULL concluído."
}

restore_full_force() {
  require_full_dir
  mkdir -p "$CODEX_DIR"

  make_current_safety_backup_full

  info "RESTORE FULL FORCE do Codex"
  info "Origem:  $FULL_HOME_DIR"
  info "Destino: $CODEX_DIR"
  info "ATENÇÃO: sobrescreve config, autenticação, tokens e credenciais."
  info "Regra: sobrescrever sem checar data."

  copy_force_item "$FULL_HOME_DIR" "$CODEX_DIR"

  info "Restore FULL FORCE concluído."
}

list_all() {
  mkdir -p "$CHATS_DIR" "$FULL_DIR"

  echo
  info "Arquivos em ./chats:"
  find "$CHATS_DIR" -maxdepth 4 \
    ! -path "$CHATS_DIR/.restore-safety*" \
    -print | sort || true

  echo
  info "Arquivos em ./chats-full:"
  find "$FULL_DIR" -maxdepth 4 \
    ! -path "$FULL_DIR/.restore-safety*" \
    -print | sort || true
}

show_where() {
  echo "CODEX_DIR=$CODEX_DIR"
  echo "CHATS_DIR=$CHATS_DIR"
  echo "FULL_DIR=$FULL_DIR"
  echo "FULL_HOME_DIR=$FULL_HOME_DIR"
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
    echo "Arquivos em ./chats:"
    find "$CHATS_DIR" -maxdepth 4 \
      ! -path "$CHATS_DIR/.restore-safety*" \
      -print | sort | head -100
  else
    echo "./chats ainda não existe."
  fi

  echo

  if [[ -d "$FULL_HOME_DIR" ]]; then
    echo "Arquivos em ./chats-full/codex-home:"
    find "$FULL_HOME_DIR" -maxdepth 3 \
      -print | sort | head -100
  else
    echo "./chats-full/codex-home ainda não existe."
  fi
}

cmd="${1:-}"
shift || true

case "$cmd" in
  backup)
    backup_chats
    ;;

  restore)
    restore_chats
    ;;

  restore-force)
    restore_chats_force
    ;;

  backup-full)
    backup_full
    ;;

  restore-full)
    restore_full
    ;;

  restore-full-force)
    restore_full_force
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