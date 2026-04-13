# ImpulseTechnologies — Facebook Feed

A WinterCMS plugin that syncs a Facebook Page's posts via the Graph API and renders them on the front end via a CMS component.

---

## Requirements

- PHP 8.1+
- WinterCMS 1.2+
- Facebook App with `pages_show_list`, `pages_read_engagement`, and `pages_manage_posts` permissions

---

## Installation

1. Place the plugin in `plugins/impulsetechnologies/facebookfeed/`
2. Run migrations:
   ```
   php artisan winter:up
   ```

---

## Configuration

Optional — override defaults by publishing the config:

```
plugins/impulsetechnologies/facebookfeed/config/config.php
```

| Key | Default | Description |
|---|---|---|
| `graph_api_version` | `v25.0` | Graph API version |
| `graph_base_url` | `https://graph.facebook.com` | Base URL |
| `posts_fields` | `message,attachments,full_picture,created_time` | Fields requested |
| `request_timeout` | `30` | cURL timeout in seconds |

---

## Setup

### 1. Create a Feed

Go to **Backend → Facebook Feed → Feeds → Create**.

| Field | Description |
|---|---|
| Name | Human-readable label |
| Code | Unique slug used in the component (e.g. `main`) |
| Facebook Page ID | Numeric page ID (e.g. `104853227550779`) |
| Page Access Token | A User or Page access token — the plugin automatically exchanges a user token for the correct Page access token on first sync |
| Sync Frequency | Display label only; scheduling defaults to daily |
| Active | Inactive feeds are skipped during scheduled sync |

### 2. Get an access token

1. Open [Graph API Explorer](https://developers.facebook.com/tools/explorer)
2. Select your Meta App
3. Under **User or Page**, select your Facebook Page
4. Add permissions: `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`
5. Click **Generate Access Token** and paste it into the Feed form

> A short-lived user token works — the plugin will auto-exchange it for a long-lived Page token on first sync and save it back.

---

## Syncing Posts

**Manual:**
```bash
php artisan facebook:sync
php artisan facebook:sync main          # specific feed by code
php artisan facebook:sync --full        # fetch all paginated pages
```

**Scheduled:** runs daily automatically via the WinterCMS scheduler. Ensure you have a cron entry:
```
* * * * * php /path/to/artisan schedule:run
```

---

## Frontend Component

Add to any CMS page or layout:

```ini
[fbFeed]
feedCode = "main"
postsPerPage = 12
sortBy = "fb_created_at"
```

```twig
{% component 'fbFeed' %}
```

### Component Properties

| Property | Default | Description |
|---|---|---|
| `feedCode` | _(required)_ | Code of the feed to display |
| `postsPerPage` | `10` | Posts per page |
| `sortBy` | `fb_created_at` | `fb_created_at` (newest first) or `sort_order` (manual) |

### Overriding the partial

Copy `components/fbfeed/default.htm` and `_post.htm` into your theme and customise freely.

---

## Backend

- **Feeds** — create and manage feed configurations
- **Posts** — toggle `is_published`, adjust `sort_order`, view raw API data
