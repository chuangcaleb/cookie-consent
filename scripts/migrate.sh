#!/bin/bash
set -e

echo "🚀 Running all SQL migrations..."

# Load environment variables from .env (if not already set)
if [ -f .env ]; then
  # Export variables from .env (skip comments)
  export $(grep -v '^#' .env | xargs)
fi

# Validate required variables
REQUIRED_VARS=("DB_HOST" "DB_PORT" "DB_USER" "DB_PASS" "DB_NAME")
for var in "${REQUIRED_VARS[@]}"; do
  if [ -z "${!var}" ]; then
    echo "❌ Missing required environment variable: $var"
    exit 1
  fi
done

# Loop through all SQL migration files in sorted order
for f in $(ls migrations/*.sql | sort); do
  echo "🔹 Applying $f ..."

  mysql \
    -h "$DB_HOST" \
    -P "${DB_PORT:-3306}" \
    -u "$DB_USER" \
    -p"$DB_PASS" \
    "$DB_NAME" < "$f"
done

echo "✅ All migrations applied successfully."
but locall