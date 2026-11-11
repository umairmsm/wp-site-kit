#!/usr/bin/env bash
# Tests for bin/loop-status and bin/loop-queue against a fixture .supervision folder and a fake
# agy-loop. No network, no tmux, no real builder. Run by tests/selftest.sh; exit 1 on any failure.
set -uo pipefail

KIT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$(mktemp -d)"
holder=""
cleanup() { [ -n "$holder" ] && kill "$holder" 2>/dev/null; rm -rf "$TMP"; }
trap cleanup EXIT
fails=0
pass() { printf 'ok    %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1"; fails=$((fails + 1)); }
has() { # name, text, expected line (fixed string)
  if grep -qF -- "$3" <<<"$2"; then pass "$1"; else fail "$1: missing «$3»"; printf '%s\n' "$2" | head -30 | while IFS= read -r l; do printf '      %s\n' "$l"; done; fi
}
rc_is() { # name, expected, actual
  if [ "$3" = "$2" ]; then pass "$1"; else fail "$1 (exit $3, expected $2)"; fi
}
hasnt() { if grep -qF -- "$3" <<<"$2"; then fail "$1: unexpected «$3»"; else pass "$1"; fi; }

REPO="$TMP/site"; S="$REPO/.supervision"
mkdir -p "$S/tasks"
task() { # file id branch steps
  { echo "# Task: $2 fixture"; echo; echo "Branch \`$3\`   Base \`main\`"; echo
    for i in $(seq 1 "$4"); do echo "### Step $i: $2-0$i, thing $i"; echo "- Do: something"; done; } > "$S/tasks/$1"
}
task TASK_AB01_ONE.md AB01 feat/ab01-one 2
task TASK_AB02_TWO.md AB02 feat/ab02-two 3
task TASK_AB03_THREE.md AB03 feat/ab03-three 2
printf '%s\n' "# fixture queue" ".supervision/tasks/TASK_AB01_ONE.md" "" ".supervision/tasks/TASK_AB02_TWO.md" \
  ".supervision/tasks/TASK_AB03_THREE.md" > "$S/QUEUE"
cat > "$S/status.conf" <<'EOF'
# fixture
TITLE=Acme build
TASK_ID_REGEX=AB[0-9]+
TMUX_SESSION=acme
NTFY_TOPIC_FILE=/nonexistent/topic
LABEL.AB01=First thing|The first plain-English line
LABEL.AB02=Second thing|The second plain-English line
EOF
NOW="$(date -u '+%F %T')"
cat > "$S/loop.log" <<EOF
2026-11-10 10:00:00 step 1/2 attempt 1: building — ### Step 1
2026-11-10 10:10:00 step 1: APPROVED escalate=False — fine
2026-11-10 10:20:00 step 2: APPROVED escalate=False — fine
2026-11-10 10:20:00 all 2 steps APPROVED on feat/ab01-one
2026-11-10 10:21:00 step 1/3 attempt 1: building — ### Step 1
2026-11-10 10:30:00 step 1: APPROVED escalate=False — fine
2026-11-10 10:31:00 step 2/3 attempt 1: building — ### Step 2
2026-11-10 10:40:00 step 2: CHANGES_REQUIRED escalate=False — fix the test
$NOW step 2/3 attempt 2: building — ### Step 2
EOF
echo '{"task":".supervision/tasks/TASK_AB02_TWO.md","step":2,"attempt":2,"prev":"","feedback":"","branch":"feat/ab02-two","pending":""}' > "$S/loop-state.json"

echo "== loop-status"
out="$("$KIT/bin/loop-status" "$REPO" 2>&1)"; rc=$?
rc_is "exits 0" 0 "$rc"
has "title" "$out" "🧱 Acme build ·"
has "done task boxes" "$out" "✅✅  done"
has "label + plain line" "$out" "AB01 First thing"
has "plain-English line" "$out" '“The first plain-English line”'
has "stopped current task (no loop running)" "$out" "✅🛑⬜  step 2/3 STOPPED"
has "unknown id shows the file name" "$out" "AB03 TASK_AB03_THREE"
has "waiting task" "$out" "⬜⬜  waiting"
has "stop hint" "$out" 'Now: 🛑 stopped at AB02 step 2. Ask Claude: “fix the stopped Acme build task”'
has "last verdict" "$out" "Last check: 🔁 step 2 changes required at 10:40"

# A process holding loop.lock open means a build is running.
( exec 9>"$S/loop.lock"; exec sleep 30 ) & holder=$!
sleep 0.5
out="$("$KIT/bin/loop-status" "$REPO" 2>&1)"
has "building box while loop.lock is held" "$out" "✅🔨⬜  step 2/3"
has "phase timer counts the current phase only" "$out" "Now: the robot is writing code on AB02 step 2 for 0 min (try 2)"
echo "$NOW step 2: gate" >> "$S/loop.log"
out="$("$KIT/bin/loop-status" "$REPO" 2>&1)"
has "checking phase" "$out" "Now: the robot is checking it (tests + reviewer) on AB02 step 2 for 0 min (try 2)"
kill "$holder" 2>/dev/null; wait "$holder" 2>/dev/null; holder=""

echo "== loop-status with missing files"
mkdir -p "$TMP/empty"
out="$("$KIT/bin/loop-status" "$TMP/empty" 2>&1)"; rc=$?
rc_is "no .supervision: exits 0" 0 "$rc"
has "no .supervision message" "$out" "No .supervision/ folder"
mkdir -p "$TMP/empty/.supervision"
out="$("$KIT/bin/loop-status" "$TMP/empty" 2>&1)"
has "no QUEUE message" "$out" "No .supervision/QUEUE yet"
printf '# only comments\n\n' > "$TMP/empty/.supervision/QUEUE"
out="$("$KIT/bin/loop-status" "$TMP/empty" 2>&1)"
has "empty QUEUE message" "$out" "The QUEUE is empty"
has "nothing running" "$out" "Now: nothing running. Start: tmux new -d -s empty loop-queue"
printf '.supervision/tasks/TASK_ZZ09_GONE.md\n' > "$TMP/empty/.supervision/QUEUE"
echo 'not json' > "$TMP/empty/.supervision/loop-state.json"
out="$("$KIT/bin/loop-status" "$TMP/empty" 2>&1)"; rc=$?
rc_is "bad state + missing task: exits 0" 0 "$rc"
has "missing task file shown" "$out" "task file missing: .supervision/tasks/TASK_ZZ09_GONE.md"

echo "== loop-queue with a fake agy-loop"
FAKE="$TMP/fake-agy-loop"
cat > "$FAKE" <<'EOF'
#!/usr/bin/env bash
# Fake agy-loop: behaviour per branch, from FAKE_PLAN ("branch=ok|fail|limit-once|outage-once").
task="$1"; log=.supervision/loop.log
branch="$(grep -oE 'Branch `[^`]+`' "$task" | head -1 | tr -d '`' | sed 's/Branch //')"
plan="$(tr ',' '\n' <<<"${FAKE_PLAN:-}" | sed -n "s|^$branch=||p")"
echo "$task" >> .supervision/fake-calls
stamp() { date -u '+%F %T'; }
case "${plan:-ok}" in
  ok) echo "$(stamp) all 2 steps APPROVED on $branch" >> "$log" ;;
  fail) echo "$(stamp) STOP: step 1 gate failed" >> "$log"; exit 1 ;;
  limit-once)
    if [ -e .supervision/limit-seen ]; then echo "$(stamp) all 2 steps APPROVED on $branch" >> "$log"
    else touch .supervision/limit-seen; echo "$(stamp) STOP: step 1 BLOCKED: You've hit your 5-hour limit · resets 5am (UTC)" >> "$log"; exit 1; fi ;;
  limit-dirty)
    # like agy-loop: refuse to start on a dirty tree; first run hits the limit mid-build and leaves edits
    if [ -n "$(git status --porcelain --untracked-files=no)" ]; then echo "$(stamp) STOP: working tree has uncommitted changes; commit or stash them first" >> "$log"; exit 1; fi
    if [ -e .supervision/dirty-seen ]; then echo "$(stamp) all 2 steps APPROVED on $branch" >> "$log"
    else touch .supervision/dirty-seen; echo half-done >> tracked.txt; echo "$(stamp) STOP: step 1 BLOCKED: You've hit your 5-hour limit · resets 1pm (UTC)" >> "$log"; exit 1; fi ;;
  outage-once)
    if [ -e .supervision/outage-seen ]; then echo "$(stamp) all 2 steps APPROVED on $branch" >> "$log"
    else touch .supervision/outage-seen; echo "$(stamp) STOP: step 2 needs your decision: All automated review models in cascade exhausted or unavailable." >> "$log"; exit 1; fi ;;
esac
EOF
chmod +x "$FAKE"
Q2="$TMP/q"; mkdir -p "$Q2/.supervision/tasks"
for i in 1 2 3; do
  # shellcheck disable=SC2016  # literal Markdown backticks
  printf 'Branch `feat/q%s`   Base `main`\n### Step 1: Q0%s\n### Step 2: Q0%s\n' "$i" "$i" "$i" > "$Q2/.supervision/tasks/TASK_Q0${i}.md"
done
printf 'NTFY_TOPIC_FILE=/nonexistent/topic\n' > "$Q2/.supervision/status.conf"
printf '.supervision/tasks/TASK_Q01.md\n.supervision/tasks/TASK_Q02.md\n' > "$Q2/.supervision/QUEUE"
echo "2026-11-10 09:00:00 all 2 steps APPROVED on feat/q1" > "$Q2/.supervision/loop.log"
run_queue() { (cd "$Q2" && LOOP_CMD="$FAKE" LOOP_RETRY_SLEEP=0 LOOP_LIMIT_SLEEP=0 LOOP_LOCK_SLEEP=0 timeout 30 "$KIT/bin/loop-queue" "$Q2"); }

FAKE_PLAN="feat/q2=limit-once" run_queue >/dev/null 2>&1; rc=$?
rc_is "usage limit: waits, retries, finishes" 0 "$rc"
calls="$(cat "$Q2/.supervision/fake-calls" 2>/dev/null)"
hasnt "already-approved task is skipped" "$calls" "TASK_Q01.md"
has "limit note in queue.log" "$(cat "$Q2/.supervision/queue.log")" "Usage limit reached in TASK_Q02.md"
has "finish note" "$(cat "$Q2/.supervision/queue.log")" "Queue finished"

printf '.supervision/tasks/TASK_Q03.md\n' >> "$Q2/.supervision/QUEUE"
FAKE_PLAN="feat/q3=outage-once" run_queue >/dev/null 2>&1; rc=$?
rc_is "reviewer outage: retries, finishes" 0 "$rc"
# Usage limit hit mid-build leaves uncommitted edits: the retry must stash them (kept, not lost) and go on.
Q4="$TMP/q4"; mkdir -p "$Q4/.supervision/tasks"
# shellcheck disable=SC2016  # literal Markdown backticks
printf 'Branch `feat/q4`   Base `main`\n### Step 1: Q04\n### Step 2: Q04\n' > "$Q4/.supervision/tasks/TASK_Q04.md"
printf 'NTFY_TOPIC_FILE=/nonexistent/topic\n' > "$Q4/.supervision/status.conf"
printf '.supervision/tasks/TASK_Q04.md\n' > "$Q4/.supervision/QUEUE"
: > "$Q4/.supervision/loop.log"
( cd "$Q4" && git init -q && printf '.supervision/\n' > .gitignore && echo base > tracked.txt && git add . && git -c user.name=t -c user.email=t@t commit -qm init )
(cd "$Q4" && FAKE_PLAN="feat/q4=limit-dirty" LOOP_CMD="$FAKE" LOOP_RETRY_SLEEP=0 LOOP_LIMIT_SLEEP=0 LOOP_LOCK_SLEEP=0 timeout 30 "$KIT/bin/loop-queue" "$Q4") >/dev/null 2>&1; rc=$?
rc_is "usage limit with leftover edits: stashes, retries, finishes" 0 "$rc"
has "leftover edits kept in a stash" "$(git -C "$Q4" stash list)" "loop-queue: partial work"

has "task appended to QUEUE is picked up" "$(cat "$Q2/.supervision/fake-calls")" "TASK_Q03.md"
has "outage note" "$(cat "$Q2/.supervision/queue.log")" "Reviewers unavailable in TASK_Q03.md; retrying in 0 min (1/4)"

# shellcheck disable=SC2016  # literal Markdown backticks
printf 'Branch `feat/q4`   Base `main`\n### Step 1: Q04\n' > "$Q2/.supervision/tasks/TASK_Q04.md"
printf '.supervision/tasks/TASK_Q04.md\n' >> "$Q2/.supervision/QUEUE"
FAKE_PLAN="feat/q4=fail" run_queue >/dev/null 2>&1; rc=$?
rc_is "real failure stops the queue (exit 1)" 1 "$rc"
has "stop note" "$(cat "$Q2/.supervision/queue.log")" "STOPPED in TASK_Q04.md (rc=1)"

printf '.supervision/tasks/TASK_MISSING.md\n' > "$Q2/.supervision/QUEUE"
run_queue >/dev/null 2>&1; rc=$?
rc_is "missing task file stops the queue" 1 "$rc"
has "missing note" "$(cat "$Q2/.supervision/queue.log")" "task file .supervision/tasks/TASK_MISSING.md not found"

( exec 8>"$Q2/.supervision/queue.lock"; flock 8; exec sleep 30 ) & holder=$!
sleep 0.5
out="$(run_queue 2>&1)"; rc=$?
rc_is "second runner refuses to start" 1 "$rc"
has "second runner message" "$out" "another loop-queue is already running"
kill "$holder" 2>/dev/null; wait "$holder" 2>/dev/null; holder=""

printf '.supervision/tasks/TASK_Q01.md\n' > "$Q2/.supervision/QUEUE"
out="$("$KIT/bin/loop-queue" raw "$Q2" 2>&1)"
has "raw: queue listing" "$out" "DONE  TASK_Q01.md (feat/q1)"
has "raw: loop not running" "$out" "== loop running: no"

if [ "$fails" -gt 0 ]; then echo "LOOP DASHBOARD: $fails failed"; exit 1; fi
echo "LOOP DASHBOARD: all passed"
