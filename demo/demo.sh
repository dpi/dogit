# dogit project:clone PROJECT
# Checkout a project
dogit project:clone scheduled_transitions
cd scheduled_transitions/
git status
ls

# dogit project:mr PROJECT
# Checkout an existing MR, by project name
dogit project:mr
>8

# dogit issue:mr ISSUE-ID
# Checkout an existing MR, by issue ID
dogit issue:mr 3073549
> 9

# dogit convert ISSUE-ID
# Convert an existing issue comprised of patches to a Git branch
dogit convert 3082728 .
