#!/bin/sh

PROTECTED_BRANCH="trunk"
CURRENT_BRANCH=$(git branch --show-current)
if [ $PROTECTED_BRANCH = $CURRENT_BRANCH ]; then
	if [ "$TERM" = "dumb" ]; then
		>&2 echo "Sorry, you are unable to push to $PROTECTED_BRANCH using a GUI client! Please use git CLI."
		exit 1
	fi

	printf "%sYou're about to push to $PROTECTED_BRANCH, is that what you intended? [y/N]: %s" "$(tput setaf 3)" "$(tput sgr0)"
	read -r PROCEED < /dev/tty
	echo

	if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" = "y" ]; then
		echo "$(tput setaf 2)Brace yourself! Pushing to the $PROTECTED_BRANCH branch...$(tput sgr0)"
		echo
		exit 0
	fi

	echo "$(tput setaf 2)Push to $PROTECTED_BRANCH cancelled!$(tput sgr0)"
	echo
	exit 1
fi

changedFiles=$(git diff $(git merge-base HEAD origin/trunk) --relative --name-only --diff-filter=d -- '.syncpackrc' 'package.json' '*/package.json')
if [ ! -z $changedFiles ]; then
	echo 'pre-push: validate syncpack mismatches'
	pnpm exec syncpack -- list-mismatches
	if [ $? -ne 0 ]; then
		echo "You must sync the dependencies listed above before you can push this branch."
		echo "This can usually be accomplished automatically by updating the pinned version in \`.syncpackrc\` and then running \`pnpm sync-dependencies\`."
		exit 1
	fi
fi

if [ ! -z $changedFiles ]; then
	echo 'pre-push: lint changes'
    ciJobs=$(CI=1 pnpm utils ci-jobs --base-ref origin/trunk --event 'pull_request' &2> 1)
    lintingJobs=$(echo $ciJobs | sed 's/::set-output/\n::set-output/g' | grep '::set-output name=lint-jobs::' | sed 's/::set-output name=lint-jobs:://g')
    echo $lintingJobs
fi

echo 'Aborting push (local development purposes)'
exit 1

