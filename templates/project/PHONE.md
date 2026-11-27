# Running the build from a phone

The build queue runs on the build machine in a tmux session, so it keeps going when you close the
terminal or your phone. Connect with any SSH app (for example Termius), then use the commands below.
**Paste one command at a time**: each block is one line on purpose (pasted multi-line blocks get joined
and silently do nothing).

## See where the build is

```
loop-queue status
```

✅ step approved · 🔨 building now · ⬜ waiting · 🛑 stopped · ⏳ waiting to retry (reviewers or usage limit).
Your phone gets a push when a task finishes or stops (if `NTFY_TOPIC_FILE` is set in `.supervision/status.conf`).

## Start the queue

Run this from the project folder (`cd` there first):

```
tmux new -d -s {{SLUG}} -c "$PWD" loop-queue
```

## Watch it live

```
tmux attach -t ={{SLUG}}
```

Leave without stopping it: press **Ctrl-b**, then **d**.

## When it stops (🛑)

Open Claude Code in the project folder and continue the last conversation:

```
claude --continue
```

Then type: **fix the stopped {{SITE_NAME}} task**. Claude reads `.supervision/loop.log`, fixes the
cause and restarts the queue. A stop is on purpose: a failing test, a decision only you can make, or a
safety rule. The queue never guesses for you.

## Plain-text details

```
loop-queue raw
```

## Add work

Append task files to `.supervision/QUEUE`, one per line. The running queue picks them up before its next task.
