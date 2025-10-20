#!/bin/bash
set -e

echo "🚀 Running all SQL migrations..."

for f in $(ls migrations/*.sql | sort); do
  echo "🔹 Applying $f ..."
  mysql -h $MYSQLHOST -P ${MYSQLPORT:-3306} \
        -u $MYSQLUSER -p$MYSQLPASSWORD \
        $MYSQLDATABASE < "$f"
done

echo "✅ All migrations applied."
