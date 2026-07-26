---
wave: 1
depends_on: []
files_modified:
  - app/Enums/DeviceType.php
autonomous: true
---

## Goal
Establish the `DeviceType` backed enum with a `fromScreenWidth()` helper method.

## Requirements
- DATA-01

## Tasks

<task id="01A-1">
<title>Create DeviceType Enum</title>
<action>
Create file `app/Enums/DeviceType.php`.
Namespace: `App\Enums`.
Define a PHP 8.3 backed enum `DeviceType` of type `string`.
Add the following cases with explicit values:
- `Mobile` = 'mobile'
- `Tablet` = 'tablet'
- `Desktop` = 'desktop'
- `Unknown` = 'unknown'

Add a public static method `fromScreenWidth(?int $width): self`.
Implementation logic inside `fromScreenWidth`:
- If `$width` is null or 0, return `self::Unknown`
- If `$width` < 768, return `self::Mobile`
- If `$width` >= 768 and `$width` <= 1024, return `self::Tablet`
- If `$width` > 1024, return `self::Desktop`
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/composer.json (reason: verify PHP version context)
</read_first>
<acceptance_criteria>
- `app/Enums/DeviceType.php` exists
- `DeviceType::Mobile->value` strictly equals `'mobile'`
- `DeviceType::fromScreenWidth(767)` returns `DeviceType::Mobile`
- `DeviceType::fromScreenWidth(768)` returns `DeviceType::Tablet`
- `DeviceType::fromScreenWidth(1025)` returns `DeviceType::Desktop`
- `DeviceType::fromScreenWidth(null)` returns `DeviceType::Unknown`
- `vendor/bin/pint --dirty --format agent` exits 0 after `app/Enums/DeviceType.php` is written
</acceptance_criteria>
</task>

## must_haves

### truths
- The `DeviceType` enum exists in the correct namespace `App\Enums`
- The helper method accurately resolves screen widths to enum cases

### prohibitions
- statement: Enum is not a backed enum
  status: resolved
  verification: Code review of `app/Enums/DeviceType.php` shows `enum DeviceType: string`

## Artifacts this phase produces
- `app/Enums/DeviceType.php` — `DeviceType` backed enum with `fromScreenWidth()` helper
