#!/usr/bin/env sh
set -eu

CODEX_HOME="${CODEX_HOME:-$HOME/.codex}"
SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
PROJECT_DIR="$(dirname -- "$SCRIPT_DIR")"
BACKUP_DIR="${BACKUP_DIR:-$PROJECT_DIR/codex-chat-backups}"
BACKUP_FILE="${1:-}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

if [ -z "$BACKUP_FILE" ]; then
    BACKUP_FILE="$(find "$BACKUP_DIR" -maxdepth 1 -type f -name 'codex-chats-*.tar.gz' 2>/dev/null | sort | tail -n 1 || true)"
fi

if [ -z "$BACKUP_FILE" ] || [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found." >&2
    echo "Usage: $0 /path/to/codex-chats-YYYYmmdd-HHMMSS.tar.gz" >&2
    echo "Or set BACKUP_DIR and omit the argument to restore the latest backup." >&2
    exit 1
fi

ARCHIVE_LIST="$(mktemp)"
INCLUDE_FILE="$(mktemp)"
trap 'rm -f "$ARCHIVE_LIST" "$INCLUDE_FILE"' EXIT

if ! tar -tzf "$BACKUP_FILE" > "$ARCHIVE_LIST"; then
    echo "Invalid backup archive: $BACKUP_FILE" >&2
    exit 1
fi

if ! awk 'BEGIN { bad = 0 } /^\/|(^|\/)\.\.(\/|$)/ { bad = 1 } END { exit bad }' "$ARCHIVE_LIST"; then
    echo "Refusing to restore an archive with unsafe paths: $BACKUP_FILE" >&2
    exit 1
fi

mkdir -p "$CODEX_HOME"

SAFETY_BACKUP_DIR="$BACKUP_DIR/pre-restore"
SAFETY_BACKUP="$SAFETY_BACKUP_DIR/codex-chats-before-restore-$TIMESTAMP.tar.gz"
mkdir -p "$SAFETY_BACKUP_DIR"

for item in \
    sessions \
    session_index.jsonl \
    attachments \
    goals_1.sqlite \
    goals_1.sqlite-shm \
    goals_1.sqlite-wal \
    logs_2.sqlite \
    logs_2.sqlite-shm \
    logs_2.sqlite-wal
do
    if [ -e "$CODEX_HOME/$item" ]; then
        printf '%s\n' "$item" >> "$INCLUDE_FILE"
    fi
done

if [ -s "$INCLUDE_FILE" ]; then
    tar -czf "$SAFETY_BACKUP" -C "$CODEX_HOME" -T "$INCLUDE_FILE"
    echo "Current Codex chats saved first to: $SAFETY_BACKUP"
fi

tar -xzf "$BACKUP_FILE" -C "$CODEX_HOME"

echo "Codex chats restored from: $BACKUP_FILE"
