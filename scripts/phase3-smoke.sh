#!/usr/bin/env bash
# Tasks 8-12 checks: glucose update/delete/date filter, food log with photo,
# medication checklist, Inner Circle codes and the read-only monitor, including
# owner isolation (a foreign id is always a 404, an unlinked patient a 403).
# Usage: scripts/phase3-smoke.sh [http://127.0.0.1:8000/temandiabet/backend]
# Needs: curl, jq, base64.
set -uo pipefail

BASE="${1:-http://127.0.0.1:8000/temandiabet/backend}"
JA="$(mktemp)"; JB="$(mktemp)"; JC="$(mktemp)"; BODY="$(mktemp)"; PNG="$(mktemp --suffix=.png)"; TXT="$(mktemp --suffix=.txt)"
STAMP="$(date +%s)-$RANDOM"
PASS=0
FAIL=0

trap 'rm -f "$JA" "$JB" "$JC" "$BODY" "$PNG" "$TXT"' EXIT

pass() { PASS=$((PASS + 1)); echo "ok   - $1"; }
fail() { FAIL=$((FAIL + 1)); echo "FAIL - $1 (got '$2', want '$3')"; }
check() { if [ "$2" = "$3" ]; then pass "$1"; else fail "$1" "$2" "$3"; fi; }

# post <jar> <path> <json>   -> prints HTTP code, body in $BODY
post() { curl -s -o "$BODY" -w '%{http_code}' -c "$1" -b "$1" -H 'Content-Type: application/json' -d "$3" "$BASE$2"; }
get()  { curl -s -o "$BODY" -w '%{http_code}' -b "$1" "$BASE$2"; }
j()    { jq -r "$1" "$BODY"; }

register() { # jar, label
  local email="phase3-$2-$STAMP@example.test"
  post "$1" /api/auth/register.php "{\"full_name\":\"Phase3 $2\",\"email\":\"$email\",\"password\":\"secret123\",\"diabetes_type\":\"type_2\"}" >/dev/null
  post "$1" /api/auth/login.php "{\"email\":\"$email\",\"password\":\"secret123\"}" >/dev/null
}

# 1x1 PNG
echo 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' | base64 -d > "$PNG"
echo 'not an image' > "$TXT"

register "$JA" a; register "$JB" b; register "$JC" c
ME_A="$(get "$JA" /api/auth/me.php >/dev/null; j .user.id)"
TODAY="$(date +%F)"

echo "== Task 8: glucose update / delete / date filter"
check "create reading" "$(post "$JA" /api/blood-glucose/create.php '{"glucose_mg_dl":150,"measurement_timing":"fasting"}')" 201
RID="$(j .record.id)"
check "150 fasting is elevated" "$(j .record.status_category)" elevated
check "update value -> high" "$(post "$JA" /api/blood-glucose/update.php "{\"id\":$RID,\"glucose_mg_dl\":200,\"measurement_timing\":\"fasting\"}")" 200
check "200 fasting reclassified high" "$(j .record.status_category)" high
post "$JA" /api/blood-glucose/update.php "{\"id\":$RID,\"glucose_mg_dl\":200,\"measurement_timing\":\"post_meal\"}" >/dev/null
check "timing change -> elevated (200 post_meal)" "$(j .record.status_category)" elevated
post "$JA" /api/blood-glucose/update.php "{\"id\":$RID,\"glucose_mg_dl\":60,\"measurement_timing\":\"random\"}" >/dev/null
check "60 -> hypoglycemia" "$(j .record.status_category)" hypoglycemia
get "$JA" "/api/blood-glucose/index.php?range=7d" >/dev/null
check "stored status persisted" "$(jq -r --argjson id "$RID" '.records[] | select(.id == $id) | .status_category' "$BODY")" hypoglycemia
check "update rejects missing timing" "$(post "$JA" /api/blood-glucose/update.php "{\"id\":$RID,\"glucose_mg_dl\":100}")" 422
check "update rejects zero" "$(post "$JA" /api/blood-glucose/update.php "{\"id\":$RID,\"glucose_mg_dl\":0,\"measurement_timing\":\"fasting\"}")" 422
check "unauthenticated update 401" "$(post /dev/null /api/blood-glucose/update.php "{\"id\":$RID,\"glucose_mg_dl\":100,\"measurement_timing\":\"fasting\"}")" 401
check "other user cannot update (404)" "$(post "$JB" /api/blood-glucose/update.php "{\"id\":$RID,\"glucose_mg_dl\":100,\"measurement_timing\":\"fasting\"}")" 404
check "other user cannot delete (404)" "$(post "$JB" /api/blood-glucose/delete.php "{\"id\":$RID}")" 404
get "$JA" "/api/blood-glucose/index.php?range=7d" >/dev/null
check "reading untouched by the other user" "$(jq -r --argjson id "$RID" '.records[] | select(.id == $id) | .glucose_mg_dl' "$BODY")" 60
check "date filter returns today's reading" "$(get "$JA" "/api/blood-glucose/index.php?from=$TODAY&to=$TODAY")" 200
check "date filter count" "$(j .summary.count)" 1
check "date filter in the past is empty" "$(get "$JA" "/api/blood-glucose/index.php?from=2000-01-01&to=2000-01-02" >/dev/null; j .summary.count)" 0
check "from after to rejected" "$(get "$JA" "/api/blood-glucose/index.php?from=2026-02-01&to=2026-01-01")" 422
check "malformed date rejected" "$(get "$JA" "/api/blood-glucose/index.php?from=nope&to=$TODAY")" 422
check "delete own reading" "$(post "$JA" /api/blood-glucose/delete.php "{\"id\":$RID}")" 200
check "delete again is 404" "$(post "$JA" /api/blood-glucose/delete.php "{\"id\":$RID}")" 404

echo "== Task 9: food log"
code="$(curl -s -o "$BODY" -w '%{http_code}' -c "$JA" -b "$JA" -F food_name='Nasi putih' -F meal_type=lunch -F estimated_calories=260 -F carbohydrates_grams=58 -F eaten_date="$TODAY" -F "photo=@$PNG;type=image/png" "$BASE/api/food/create.php")"
check "create meal with photo" "$code" 201
FID="$(j .meal.id)"; PHOTO="$(j .meal.photo_path)"
check "photo path is portable" "$(echo "$PHOTO" | grep -c '^uploads/foods/')" 1
check "photo is served" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$PHOTO")" 200
check "create meal without photo" "$(post "$JA" /api/food/create.php "{\"food_name\":\"Teh tawar\",\"meal_type\":\"snack\",\"estimated_calories\":2,\"carbohydrates_grams\":0,\"eaten_date\":\"$TODAY\"}")" 201
check "invalid meal_type rejected" "$(post "$JA" /api/food/create.php '{"food_name":"X","meal_type":"brunch"}')" 422
check "empty name rejected" "$(post "$JA" /api/food/create.php '{"food_name":"","meal_type":"lunch"}')" 422
check "non-image upload rejected" "$(curl -s -o /dev/null -w '%{http_code}' -b "$JA" -F food_name=X -F meal_type=lunch -F "photo=@$TXT;type=image/png" "$BASE/api/food/create.php")" 400
get "$JA" "/api/food/index.php?eaten_date=$TODAY" >/dev/null
check "two meals listed" "$(j '.meals | length')" 2
check "daily calories total" "$(j .totals.estimated_calories)" 262
check "daily carbs total" "$(j .totals.carbohydrates_grams)" 58
check "bad eaten_date rejected" "$(get "$JA" "/api/food/index.php?eaten_date=31-12-2026")" 422
get "$JB" "/api/food/index.php?eaten_date=$TODAY" >/dev/null
check "other user sees no meals" "$(j '.meals | length')" 0
check "other user cannot delete (404)" "$(post "$JB" /api/food/delete.php "{\"id\":$FID}")" 404
check "photo survives a foreign delete attempt" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$PHOTO")" 200
check "delete own meal" "$(post "$JA" /api/food/delete.php "{\"id\":$FID}")" 200
check "photo file removed with the meal" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$PHOTO")" 404
get "$JA" "/api/food/index.php?eaten_date=$TODAY" >/dev/null
check "totals after delete" "$(j .totals.estimated_calories)" 2

echo "== Task 10: medication checklist"
check "create 20:00 entry" "$(post "$JA" /api/medication/create.php "{\"medication_name\":\"Metformin\",\"dosage\":\"500 mg\",\"scheduled_time\":\"20:00\",\"scheduled_date\":\"$TODAY\"}")" 201
M1="$(j .item.id)"
check "create 07:30 entry" "$(post "$JA" /api/medication/create.php "{\"medication_name\":\"Gliclazide\",\"dosage\":\"1 tablet\",\"scheduled_time\":\"07:30\",\"scheduled_date\":\"$TODAY\"}")" 201
M2="$(j .item.id)"
check "bad time rejected" "$(post "$JA" /api/medication/create.php '{"medication_name":"X","dosage":"1","scheduled_time":"25:99"}')" 422
check "missing dosage rejected" "$(post "$JA" /api/medication/create.php '{"medication_name":"X","scheduled_time":"08:00"}')" 422
get "$JA" "/api/medication/index.php?scheduled_date=$TODAY" >/dev/null
check "sorted by time" "$(j '.items[0].medication_name')" Gliclazide
check "starts at 0/2" "$(j '"\(.progress.taken)/\(.progress.total)"')" 0/2
check "toggle on" "$(post "$JA" /api/medication/toggle.php "{\"id\":$M2}")" 200
check "toggled to 1" "$(j .is_taken)" 1
get "$JA" "/api/medication/index.php?scheduled_date=$TODAY" >/dev/null
check "persisted 1/2" "$(j '"\(.progress.taken)/\(.progress.total)"')" 1/2
check "explicit is_taken false" "$(post "$JA" /api/medication/toggle.php "{\"id\":$M2,\"is_taken\":false}")" 200
check "back to 0" "$(j .is_taken)" 0
post "$JA" /api/medication/toggle.php "{\"id\":$M2,\"is_taken\":true}" >/dev/null
check "other user cannot toggle (404)" "$(post "$JB" /api/medication/toggle.php "{\"id\":$M1}")" 404
check "other user cannot delete (404)" "$(post "$JB" /api/medication/delete.php "{\"id\":$M1}")" 404
check "delete own entry" "$(post "$JA" /api/medication/delete.php "{\"id\":$M1}")" 200
get "$JA" "/api/medication/index.php?scheduled_date=$TODAY" >/dev/null
check "1/1 after delete" "$(j '"\(.progress.taken)/\(.progress.total)"')" 1/1

echo "== Task 11: Inner Circle"
check "unauthenticated generate 401" "$(post /dev/null /api/inner-circle/generate-code.php '{}')" 401
check "A generates a code" "$(post "$JA" /api/inner-circle/generate-code.php '{}')" 201
CODE="$(j .invite.invite_code)"; PEND="$(j .invite.id)"
check "code format" "$(echo "$CODE" | grep -Ec '^GLK-[A-Z0-9]{5}$')" 1
check "A cannot claim own code" "$(post "$JA" /api/inner-circle/join.php "{\"invite_code\":\"$CODE\",\"relationship\":\"friend\"}")" 422
check "unknown code 404" "$(post "$JB" /api/inner-circle/join.php '{"invite_code":"GLK-ZZZZZ","relationship":"friend"}')" 404
check "malformed code 422" "$(post "$JB" /api/inner-circle/join.php '{"invite_code":"hello","relationship":"friend"}')" 422
check "relationship required" "$(post "$JB" /api/inner-circle/join.php "{\"invite_code\":\"$CODE\"}")" 422
check "invalid relationship rejected" "$(post "$JB" /api/inner-circle/join.php "{\"invite_code\":\"$CODE\",\"relationship\":\"boss\"}")" 422
check "monitor before joining is 403" "$(get "$JB" "/api/inner-circle/monitor.php?patient_id=$ME_A")" 403
check "B claims the code (lowercase ok)" "$(post "$JB" /api/inner-circle/join.php "{\"invite_code\":\"$(echo "$CODE" | tr 'A-Z' 'a-z')\",\"relationship\":\"child\"}")" 200
check "claimed code rejected for C (409)" "$(post "$JC" /api/inner-circle/join.php "{\"invite_code\":\"$CODE\",\"relationship\":\"friend\"}")" 409
check "claimed code rejected for B again (409)" "$(post "$JB" /api/inner-circle/join.php "{\"invite_code\":\"$CODE\",\"relationship\":\"child\"}")" 409
get "$JA" /api/inner-circle/index.php >/dev/null
check "A lists B as caregiver" "$(j '.caregivers_of_me | length')" 1
check "A sees the stored relationship" "$(j '.caregivers_of_me[0].relationship')" child
check "no sensitive fields in the list" "$(grep -c -E 'password_hash|remember_token' "$BODY")" 0
get "$JB" /api/inner-circle/index.php >/dev/null
check "B lists A as monitored" "$(j '.patients_i_monitor | length')" 1
check "B sees the stored relationship" "$(j '.patients_i_monitor[0].relationship')" child
LINK="$(j '.patients_i_monitor[0].id')"

# A second code B tries to use for the same patient -> already linked.
post "$JA" /api/inner-circle/generate-code.php '{}' >/dev/null; CODE2="$(j .invite.invite_code)"; PEND2="$(j .invite.id)"
check "same pair cannot link twice (409)" "$(post "$JB" /api/inner-circle/join.php "{\"invite_code\":\"$CODE2\",\"relationship\":\"child\"}")" 409
check "A removes the unused code" "$(post "$JA" /api/inner-circle/remove.php "{\"id\":$PEND2}")" 200
check "B cannot remove A's pending code (404)" "$(post "$JB" /api/inner-circle/remove.php "{\"id\":$PEND}")" 404

echo "== Task 12: read-only monitor"
post "$JA" /api/blood-glucose/create.php '{"glucose_mg_dl":45,"measurement_timing":"fasting"}' >/dev/null
check "B monitors A" "$(get "$JB" "/api/inner-circle/monitor.php?patient_id=$ME_A")" 200
check "patient name returned" "$(j .patient.full_name)" "Phase3 a"
check "latest reading is the 45" "$(j .latest.glucose_mg_dl)" 45
check "latest status hypoglycemia" "$(j .latest.status_category)" hypoglycemia
check "7-day readings included" "$(j '.glucose.records | length')" 1
check "medication progress included" "$(j '"\(.medication.progress.taken)/\(.medication.progress.total)"')" 1/1
check "no password_hash in payload" "$(grep -c password_hash "$BODY")" 0
check "no remember_token in payload" "$(grep -c remember_token "$BODY")" 0
check "no email in payload" "$(grep -c '@example.test' "$BODY")" 0
check "C (unlinked) gets 403" "$(get "$JC" "/api/inner-circle/monitor.php?patient_id=$ME_A")" 403
check "A cannot monitor B (not a caregiver of B) 403" "$(get "$JA" "/api/inner-circle/monitor.php?patient_id=$(get "$JB" /api/auth/me.php >/dev/null; j .user.id)")" 403
check "missing patient_id 422" "$(get "$JB" /api/inner-circle/monitor.php)" 422
check "unauthenticated monitor 401" "$(get /dev/null "/api/inner-circle/monitor.php?patient_id=$ME_A")" 401
check "C cannot remove the A-B link (404)" "$(post "$JC" /api/inner-circle/remove.php "{\"id\":$LINK}")" 404
check "B (caregiver) can leave" "$(post "$JB" /api/inner-circle/remove.php "{\"id\":$LINK}")" 200
check "after B leaves, monitor is 403" "$(get "$JB" "/api/inner-circle/monitor.php?patient_id=$ME_A")" 403

# Revocation from the patient's side.
post "$JA" /api/inner-circle/generate-code.php '{}' >/dev/null; CODE3="$(j .invite.invite_code)"
post "$JC" /api/inner-circle/join.php "{\"invite_code\":\"$CODE3\",\"relationship\":\"friend\"}" >/dev/null
check "C monitors A" "$(get "$JC" "/api/inner-circle/monitor.php?patient_id=$ME_A")" 200
get "$JA" /api/inner-circle/index.php >/dev/null; LINK2="$(j '.caregivers_of_me[0].id')"
check "A (patient) revokes C" "$(post "$JA" /api/inner-circle/remove.php "{\"id\":$LINK2}")" 200
check "after A revokes, C gets 403" "$(get "$JC" "/api/inner-circle/monitor.php?patient_id=$ME_A")" 403

echo
echo "passed: $PASS  failed: $FAIL   (users phase3-{a,b,c}-$STAMP@example.test)"
[ "$FAIL" -eq 0 ] || exit 1

# shortcut: leaves the users, their rows and the uploaded photo behind, wipe with
# DELETE FROM users WHERE email LIKE 'phase3-%';
