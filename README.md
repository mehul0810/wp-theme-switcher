# Smart Theme Switcher

**Preview, test, and assign any installed WordPress theme to individual pages, posts, or taxonomy archives—privately and instantly. Build and redesign without affecting your live site!**

---

## 🚀 Features

- **Per-resource Theme Preview:** Instantly preview any installed theme on specific posts, pages, or taxonomy terms—visible only to logged-in users.
- **Block Editor Integration:** “Theme Preview” panel in the Block Editor sidebar for quick switching via dropdown.
- **Frontend Preview Banner:** Visual indicator and quick-switcher for theme previews (only for admins/editors).
- **Safe & Non-Destructive:** Preview changes without affecting site visitors or SEO.
- **Supports Block & Classic Themes:** Test FSE (block) and classic themes interchangeably.
- **Modern Settings UI:** Configure plugin options with a clean, WP Design System-based interface.

---

## 📝 How It Works

1. **Install & Activate** the plugin.
2. **Edit any post, page, or taxonomy term** in the Block Editor.
3. Use the **“Theme Preview” panel** in the sidebar to select an installed theme.
4. The editor and frontend preview will reload, showing the resource with the selected theme (only for you).
5. When previewing, you’ll see a banner at the top of the page with the current theme name and options to switch or exit preview.
6. Visitors and search engines always see your default active theme.

---

## 🛠️ Installation

1. Upload the plugin to `/wp-content/plugins/smart-theme-switcher` directory.
2. Activate via **Plugins > Installed Plugins** in WordPress admin.
3. (Optional) Configure options under **Settings > Smart Theme Switcher**.

---

## ⚙️ Settings

- **Enable/disable preview banner**
- **Set default preview theme**
- (Advanced) **Change preview query parameter name**

Accessible under **Settings > Smart Theme Switcher** (requires `manage_options` capability).

---

## 🔐 Security & Permissions

- Only logged-in users with appropriate permissions (`edit_posts` or higher) can access theme preview.
- Nonces secure preview links.
- Theme previews are never visible to visitors or bots.

---

## ❓ FAQ

**Q: Will previewing a theme affect my live site or SEO?**  
A: No. All previews are private to logged-in users. Visitors and Google will see only your active theme.

**Q: Can I use this to safely design with block/FSE or classic themes before launch?**  
A: Yes! You can design and experiment with any theme, privately, on any resource.

**Q: Is this multisite compatible?**  
A: Not in MVP. Future versions may add support.

---

## 🧑‍💻 Developer Notes

- Uses WordPress Coding Standards (WPCS).
- Block Editor panel uses React (`@wordpress/components`).
- Preview logic uses `template_include` and user/session checks.
- Banner and admin bar use custom scripts/styles loaded only for preview mode.

---

## 🗺️ Roadmap

- [ ] Role-based preview access
- [ ] Full-site preview mode
- [ ] Analytics/logging
- [ ] Multisite/network support

---

## 🙏 Credits

Developed by [Mehul Gohil](https://mehulgohil.com)  
Inspired by the WordPress community’s need for frictionless theme design and testing.

---

## 📄 License

GPLv2 or later.  
Copyright (c) [Mehul Gohil]

---

