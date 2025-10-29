# Installation Options for license-manager

Since this package is installed from a Git repository (VCS), you have several options:

## Option 1: Use Latest Code (Recommended for Development)

In `agent-panel/composer.json`:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Sudhir-Pratap/license-manager.git"
        }
    ],
    "require": {
        "acecoderz/license-manager": "dev-master"
    }
}
```

Then install:
```bash
composer update acecoderz/license-manager
```

This will always use the latest commit on the `master` branch.

## Option 2: Use Specific Tag (Recommended for Production)

```json
{
    "require": {
        "acecoderz/license-manager": "v1.0.1"
    }
}
```

Then install:
```bash
composer update acecoderz/license-manager
```

## Option 3: Use Branch Name Directly

```json
{
    "require": {
        "acecoderz/license-manager": "dev-master"
    }
}
```

## Option 4: Use Any Branch

```json
{
    "require": {
        "acecoderz/license-manager": "dev-your-branch-name"
    }
}
```

## Current Recommendation

**For Local Development/Testing:**
```json
"acecoderz/license-manager": "dev-master"
```
- Gets latest fixes immediately
- No need to wait for tags
- Good for testing new features

**For Production:**
```json
"acecoderz/license-manager": "v1.0.1"
```
- Stable, tagged version
- Better for production deployments
- Can pin to specific version

## Update Commands

**To update to latest:**
```bash
composer update acecoderz/license-manager
```

**To update to specific tag:**
```bash
composer require acecoderz/license-manager:v1.0.1
```

## Notes

- `dev-master` means "development version from master branch"
- Tags (like `v1.0.1`) are stable releases
- After updating, always run: `php artisan config:clear`

