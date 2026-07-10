# Update the existing GitHub repository

This upgraded package is designed to replace the working tree in the existing repository while preserving commit history.

## 1. Clone the existing repository

```powershell
cd "C:\Users\showmik\Downloads"
git clone https://github.com/SAHARIARSHOWMIK/vendor-compliance-portal.git vendor-compliance-portal-repo-update
```

## 2. Mirror the upgraded files into the clone

```powershell
$source = "C:\Users\showmik\Downloads\vendor-compliance-portal-upgraded-final"
$target = "C:\Users\showmik\Downloads\vendor-compliance-portal-repo-update"

robocopy $source $target /MIR /XD ".git" "vendor" "node_modules" "public\build" ".phpunit.cache" /XF ".env" "*.sqlite" "*.sqlite3"
```

A Robocopy exit code from 0 through 7 normally indicates success.

## 3. Review and commit the complete upgrade

```powershell
cd "C:\Users\showmik\Downloads\vendor-compliance-portal-repo-update"
git status
git add -A
git commit -m "Upgrade vendor compliance platform and operations experience"
git push origin main
```

## 4. Verify CI

Open the Actions tab and check the newest workflow run. Older workflow runs remain in the repository history and do not need to be removed.
