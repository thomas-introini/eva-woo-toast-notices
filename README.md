## Eva Toast Notices

Replaces standard WordPress and WooCommerce notices with JavaScript toast notifications in both the admin area and the frontend.

### Features

- **Admin notices interception**: Captures core/theme/plugin admin notices and converts them into JS toasts.
- **WooCommerce notices override**: Converts WooCommerce frontend notices to toasts (cart, checkout, shop, my account, etc.).
- **Accessible, dependency-free toasts**: Uses a minimal Toastify-compatible implementation with ARIA roles and keyboard focus support.
- **Configurable behavior**: Enable/disable for admin/frontend, toggle WooCommerce override, choose position and timeout.

### Local development with Docker

From the project root (`/home/thomas/projects/eva-woo-toast-notices`):

```bash
./scripts/start.sh
./scripts/install-wordpress.sh
```

Then open `http://localhost:8080` in your browser.

To stop the environment:

```bash
./scripts/stop.sh
```

The plugin is mounted into the `wordpress` container at:

`wp-content/plugins/eva-toast-notices/`


