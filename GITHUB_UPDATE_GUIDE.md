# Update the existing GitHub repository

This repaired package replaces the working tree in the existing repository while preserving its Git history.

## 1. Extract this package

Extract the ZIP into Downloads. The source folder should be:

```text
C:\Users\showmik\Downloads\vendor-compliance-portal-repaired-final
```

## 2. Clone the existing repository

```powershell
cd "C:\Users\showmik\Downloads"
git clone https://github.com/SAHARIARSHOWMIK/vendor-compliance-portal.git vendor-compliance-portal-final-update
```

## 3. Mirror the repaired files into the clone

```powershell
$source = "C:\Users\showmik\Downloads\vendor-compliance-portal-repaired-final"
$target = "C:\Users\showmik\Downloads\vendor-compliance-portal-final-update"

robocopy $source $target /MIR /XD ".git" "vendor" "node_modules" "public\build" ".phpunit.cache" /XF ".env" "*.sqlite" "*.sqlite3" "*.db"
```

A Robocopy exit code from 0 through 7 normally indicates success.

## 4. Review, commit, and push the complete replacement

```powershell
cd "C:\Users\showmik\Downloads\vendor-compliance-portal-final-update"
git status
git add -A
git commit -m "Stabilize vendor compliance platform and CI"
git push origin main
```

## 5. Verify the newest CI run

Open the repository Actions tab and inspect only the workflow triggered by the new commit. Older failed runs remain in history and do not affect the current branch.
