#!/usr/bin/env bash
set -uo pipefail

BASE="${1:-http://127.0.0.1:8000/backend}"
JAR="$(mktemp)"; JAR2="$(mktemp)"; BODY="$(mktemp)"
EMAIL="glucose-$(date +%s)-$RANDOM@example.test"
EMAIL2="glucose-other-$(date +%s)-$RANDOM@example.test"
PASS=0
FAIL=0

trap 'rm -f "$JAR" "$JAR2" "$BODY"' EXIT

pass() { PASS=$((PASS + 1)); echo "ok   - $1"; }
fail() { FAIL=$((FAIL + 1)); echo "FAIL - $1 (got '$2', want '$3')"; }
check() { if [ "$2" = "$3" ]; then pass "$1"; else fail "$1" "$2" "$3"; fi; }

post() {
  curl -s -o "$BODY" -w '%{http_code}' -c "${3:-$JAR}" -b "${3:-$JAR}" \
    -H 'Content-Type: application/json' -d "$2" "$BASE$1"
}

register() {
  local code
  code="$(post /api/auth/register.php \
    "{\"full_name\":\"Glucose Test\",\"email\":\"$1\",\"password\":\"secret123\",\"diabetes_type\":\"type_2\"}" "$2")"
  post /api/auth/login.php "{\"email\":\"$1\",\"password\":\"secret123\"}" "$2" >/dev/null
  echo "$code"
}

case_check() {
  local code status
  code="$(post /api/blood-glucose/create.php \
    "{\"glucose_mg_dl\":$1,\"measurement_timing\":\"$2\"}")"
  status="$(jq -r '.record.status_category // .message' "$BODY")"
  if [ "$code" = 201 ] && [ "$status" = "$3" ]; then
    pass "$1 mg/dL $2 -> $3"
  else
    fail "$1 mg/dL $2" "HTTP $code $status" "201 $3"
  fi
}

check "unauthenticated create rejected" "$(post /api/blood-glucose/create.php '{"glucose_mg_dl":100,"measurement_timing":"fasting"}' "$JAR2")" 401
check "register" "$(register "$EMAIL" "$JAR")" 201
register "$EMAIL2" "$JAR2" >/dev/null

check "zero rejected" "$(post /api/blood-glucose/create.php '{"glucose_mg_dl":0,"measurement_timing":"fasting"}')" 422
check "negative rejected" "$(post /api/blood-glucose/create.php '{"glucose_mg_dl":-5,"measurement_timing":"fasting"}')" 422
check "missing timing rejected" "$(post /api/blood-glucose/create.php '{"glucose_mg_dl":100}')" 422
check "unknown timing rejected" "$(post /api/blood-glucose/create.php '{"glucose_mg_dl":100,"measurement_timing":"after_snack"}')" 422
check "bad measured_at rejected" "$(post /api/blood-glucose/create.php '{"glucose_mg_dl":100,"measurement_timing":"fasting","measured_at":"yesterday"}')" 422

case_check 65 fasting hypoglycemia
case_check 69 fasting hypoglycemia
case_check 70 fasting normal
case_check 110 fasting normal
case_check 130 fasting normal
case_check 131 fasting elevated
case_check 165 fasting elevated
case_check 179 fasting elevated
case_check 180 fasting high
case_check 230 fasting high

case_check 70 post_meal normal
case_check 179 post_meal normal
case_check 180 post_meal elevated
case_check 200 post_meal elevated
case_check 249 post_meal elevated
case_check 250 post_meal high

case_check 179 random normal
case_check 180 random elevated
case_check 199 random elevated
case_check 200 random high

curl -s -o "$BODY" -b "$JAR" "$BASE/api/blood-glucose/index.php?range=7d"
check "7d returns all 20 readings" "$(jq -r '.summary.count' "$BODY")" 20
check "persisted hypoglycemia count" "$(jq '[.records[] | select(.status_category == "hypoglycemia")] | length' "$BODY")" 2
check "persisted normal count" "$(jq '[.records[] | select(.status_category == "normal")] | length' "$BODY")" 6
check "persisted elevated count" "$(jq '[.records[] | select(.status_category == "elevated")] | length' "$BODY")" 8
check "persisted high count" "$(jq '[.records[] | select(.status_category == "high")] | length' "$BODY")" 4
check "summary min" "$(jq -r '.summary.min' "$BODY")" 65
check "summary max" "$(jq -r '.summary.max' "$BODY")" 250
check "summary avg is numeric" "$(jq -r '.summary.avg | type' "$BODY")" number

curl -s -o "$BODY" -b "$JAR" "$BASE/api/blood-glucose/index.php?range=30d"
check "30d also returns the readings" "$(jq -r '.summary.count' "$BODY")" 20

curl -s -o "$BODY" -b "$JAR2" "$BASE/api/blood-glucose/index.php?range=7d"
check "another user sees no readings" "$(jq -r '.summary.count' "$BODY")" 0
check "another user gets no average" "$(jq -r '.summary.avg' "$BODY")" null

echo
echo "passed: $PASS  failed: $FAIL   (users $EMAIL, $EMAIL2)"
[ "$FAIL" -eq 0 ] || exit 1

