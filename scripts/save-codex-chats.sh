#!/usr/bin/env sh
set -eu

CODEX_HOME="${CODEX_HOME:-$HOME/.codex}"
SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
PROJECT_DIR="$(dirname -- "$SCRIPT_DIR")"
BACKUP_DIR="${BACKUP_DIR:-$PROJECT_DIR/codex-chat-backups}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_FILE="${BACKUP_FILE:-$BACKUP_DIR/codex-chats-$TIMESTAMP.tar.gz}"

if [ ! -d "$CODEX_HOME" ]; then
    echo "Codex directory not found: $CODEX_HOME" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

INCLUDE_FILE="$(mktemp)"
trap 'rm -f "$INCLUDE_FILE"' EXIT

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

if [ ! -s "$INCLUDE_FILE" ]; then
    echo "No Codex chat files found in: $CODEX_HOME" >&2
    exit 1
fi

tar -czf "$BACKUP_FILE" -C "$CODEX_HOME" -T "$INCLUDE_FILE"

echo "Codex chats saved to: $BACKUP_FILE"
