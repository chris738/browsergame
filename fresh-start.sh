#!/bin/bash

# Browsergame Fresh Start - Convenience Wrapper
# Direkter Aufruf von der Projektroot aus
# Direct call from project root

# Navigate to script directory and execute fresh-start.sh
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "$SCRIPT_DIR/scripts/fresh-start.sh" "$@"