# Google Drive Setup for TMS Backups

## Root cause of "Google Drive skipped (not configured)"

Audit findings:

| Check | Result |
|-------|--------|
| `config/google-drive-service-account.json` | **Missing** (only `.example.json` existed) |
| `google/apiclient` Composer package | **Was missing** — now installed (`^2.18`) |
| `enabled` in config | `true` |
| Folder ID | Empty (optional until credentials exist) |

Because the service-account JSON file was absent, `GoogleDriveService::isConfigured()` returned `false`, and the backup engine logged **Google Drive skipped (not configured)**.

## What was fixed in code

1. Installed **`google/apiclient`**
2. Rewrote **`GoogleDriveService`** to use the official SDK (JWT+cURL fallback remains)
3. Added **`config/google-drive.php`** (enabled, folder_id, credentials path, retries, timeout)
4. **SKIPPED** is used **only** when `enabled = false`
5. If Drive is enabled but not ready → status **FAILED** / **PARTIAL**, local ZIP kept, error logged, **Retry Upload** available
6. Dashboard panel shows Connected / Folder / Last Upload / Link
7. Column **`google_drive_uploaded_at`** added to `backup_logs`

## Required: create the service account key (you must do this)

TMS cannot invent Google Cloud credentials. Complete these steps once:

### 1. Google Cloud Console

1. Open https://console.cloud.google.com/
2. Create or select a project
3. **APIs & Services → Library → Google Drive API → Enable**
4. **IAM & Admin → Service Accounts → Create service account**
   - Name: `tms-backup`
5. Open the service account → **Keys → Add key → JSON** → download the file
6. Save/rename the downloaded file to:

```text
C:\wamp64\www\tms\config\google-drive-service-account.json
```

(Do **not** commit this file to git.)

### 2. Shared folder (recommended so files appear in *your* Drive)

1. In Google Drive (your user account), create folder: **TMS Database Backups**
2. Right-click → **Share** → add the service account email  
   (looks like `tms-backup@YOUR_PROJECT.iam.gserviceaccount.com`) as **Editor**
3. Open the folder and copy the ID from the URL:

```text
https://drive.google.com/drive/folders/THIS_IS_THE_FOLDER_ID
```

4. Paste into `config/google-drive.php`:

```php
'folder_id' => getenv('TMS_GDRIVE_FOLDER_ID') ?: 'THIS_IS_THE_FOLDER_ID',
```

### 3. Configuration reference (`config/google-drive.php`)

| Option | Purpose |
|--------|---------|
| `enabled` | Master switch (`false` → SKIPPED) |
| `folder_id` | Target Drive folder |
| `folder_name` | Used if folder must be auto-created |
| `credentials_path` | Path to JSON key |
| `retry_upload` | Auto-retry failed uploads |
| `max_upload_retries` | Cap per backup |
| `upload_timeout` | Seconds |

Optional environment variables:

- `GOOGLE_APPLICATION_CREDENTIALS` — absolute path to JSON key  
- `TMS_GDRIVE_FOLDER_ID` — folder ID override  

### 4. Verify

1. Refresh Backup page → **Google Drive Connected: Yes**
2. Click **Backup Now**
3. Confirm ZIP appears in Drive folder
4. History row shows destination `LOCAL+GOOGLE_DRIVE` and Drive status `UPLOADED`
5. For older local-only backups, use **Retry Upload**

## Composer package

```bash
composer require google/apiclient:^2.18
```

Already added to this project’s `composer.json` / `vendor/`.

## Folder permission verification

If upload fails with permission / not found:

1. Confirm Drive API is enabled
2. Confirm JSON key matches the service account email you shared with
3. Confirm that email has **Editor** on the folder
4. Confirm `folder_id` is correct (no extra spaces)
5. Click **Refresh Status** on the Backup page
