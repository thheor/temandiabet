#!/usr/bin/env bash
# End-to-end check for Tasks 3-6: register, session, remember-me, profile
# update with avatar, upload rejection, logout.
# Usage: scripts/auth-smoke.sh [http://127.0.0.1:8000/temandiabet/backend]
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE="${1:-http://127.0.0.1:8000/temandiabet/backend}"
JAR="$(mktemp)"; JAR_REMEMBER="$(mktemp)"
BODY="$(mktemp)"; PNG="$(mktemp)"; TXT="$(mktemp)"; BIG="$(mktemp)"
EMAIL="smoke-$(date +%s)-$RANDOM@example.test"
PASS=0
FAIL=0

trap 'rm -f "$JAR" "$JAR_REMEMBER" "$BODY" "$PNG" "$TXT" "$BIG"' EXIT

base64 -d >"$PNG" <<'B64'
iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==
B64
echo "not an image" >"$TXT"

pass() { PASS=$((PASS + 1)); echo "ok   - $1"; }
fail() { FAIL=$((FAIL + 1)); echo "FAIL - $1 (got '$2', want '$3')"; }
check() { if [ "$2" = "$3" ]; then pass "$1"; else fail "$1" "$2" "$3"; fi; }

post_json() {
  curl -s -o "$BODY" -w '%{http_code}' -c "$JAR" -b "$JAR" \
    -H 'Content-Type: application/json' -d "$2" "$BASE$1"
}
get_me() {
  curl -s -o "$BODY" -w '%{http_code}' -b "${1:-$JAR}" "$BASE/api/auth/me.php"
}

REGISTER="{\"full_name\":\"Smoke Test\",\"email\":\"$EMAIL\",\"password\":\"secret123\",\"diabetes_type\":\"type_2\"}"

check "register" "$(post_json /api/auth/register.php "$REGISTER")" 201
check "duplicate email rejected" "$(post_json /api/auth/register.php "$REGISTER")" 409
check "short password rejected" \
  "$(post_json /api/auth/register.php "{\"full_name\":\"A B\",\"email\":\"x-$EMAIL\",\"password\":\"short\",\"diabetes_type\":\"type_2\"}")" 422
check "unauthenticated me" "$(get_me)" 401
check "wrong password rejected" \
  "$(post_json /api/auth/login.php "{\"email\":\"$EMAIL\",\"password\":\"wrong123\"}")" 401
check "login with remember-me" \
  "$(post_json /api/auth/login.php "{\"email\":\"$EMAIL\",\"password\":\"secret123\",\"remember\":true}")" 200
check "me after login" "$(get_me)" 200 && [ "$(jq -r .user.full_name "$BODY")" = "Smoke Test" ] && pass "session carries the user"

grep -v PHPSESSID "$JAR" >"$JAR_REMEMBER"
check "remember-me cookie restores the session" "$(get_me "$JAR_REMEMBER")" 200

check "profile update (json)" \
  "$(post_json /api/profile/update.php "{\"full_name\":\"Smoke Tester\",\"diabetes_type\":\"type_1\",\"target_glucose_min\":75,\"target_glucose_max\":140}")" 200
check "targets persisted" "$(jq -r '.user.target_glucose_max' "$BODY")" 140
check "invalid target range rejected" \
  "$(post_json /api/profile/update.php "{\"target_glucose_min\":200,\"target_glucose_max\":100}")" 422

check "avatar upload" "$(
  curl -s -o "$BODY" -w '%{http_code}' -c "$JAR" -b "$JAR" \
    -F "avatar=@$PNG;type=image/png" "$BASE/api/profile/update.php"
)" 200
AVATAR="$(jq -r .user.avatar_path "$BODY")"
if [ -f "$ROOT/backend/$AVATAR" ]; then pass "avatar stored at $AVATAR"; else fail "avatar file missing" "$AVATAR" "existing file"; fi
check "avatar is served" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$AVATAR")" 200

PREVIOUS="$AVATAR"
check "second avatar upload" "$(
  curl -s -o "$BODY" -w '%{http_code}' -c "$JAR" -b "$JAR" \
    -F "avatar=@$PNG;type=image/png" "$BASE/api/profile/update.php"
)" 200
AVATAR="$(jq -r .user.avatar_path "$BODY")"
if [ -f "$ROOT/backend/$AVATAR" ] && [ ! -f "$ROOT/backend/$PREVIOUS" ]; then
  pass "replaced avatar file removed"
else
  fail "replaced avatar file removed" "$PREVIOUS" "deleted, $AVATAR kept"
fi
check "non-image upload rejected" "$(
  curl -s -o "$BODY" -w '%{http_code}' -c "$JAR" -b "$JAR" \
    -F "avatar=@$TXT;type=text/plain" "$BASE/api/profile/update.php"
)" 400
head -c 3000000 /dev/urandom >"$BIG"
check "oversized upload rejected" "$(
  curl -s -o "$BODY" -w '%{http_code}' -c "$JAR" -b "$JAR" \
    -F "avatar=@$BIG;type=image/png" "$BASE/api/profile/update.php"
)" 400

check "logout" "$(post_json /api/auth/logout.php '{}')" 200
check "me after logout" "$(get_me)" 401
check "remember-me cookie is dead after logout" "$(get_me "$JAR_REMEMBER")" 401

echo
echo "passed: $PASS  failed: $FAIL   (user $EMAIL)"
[ "$FAIL" -eq 0 ] || exit 1

# shortcut: leaves the smoke user and avatar in the dev database, wipe with
# `docker compose down -v` or DELETE FROM users WHERE email LIKE 'smoke-%'.
