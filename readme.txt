=== Crossmint Payment Plugin ===
Contributors: Kiran Poudel
Donate link: https://yourwebsite.com/donate
Tags: crossmint, payment, crypto, fiat, blockchain, nfts, digital assets, qoin
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple WordPress plugin to accept fiat and crypto payments via Crossmint.

== Description ==

The **Crossmint Payment Plugin** enables WordPress site owners to accept payments in both fiat currencies (like USD) and cryptocurrencies using Crossmint’s payment gateway. Crossmint simplifies blockchain-based payments by managing wallet creation and cross-chain transactions, so users can pay easily without needing their own crypto wallet.

### Key Features
- Seamless integration with Crossmint’s client-side SDK.
- Shortcode `[crossmint_payment]` to add a payment button anywhere on your site.
- Admin settings page to configure your Crossmint Client ID.
- Supports both fiat and crypto payments without requiring users to set up a wallet.

This plugin is perfect for creators, merchants, and developers looking to accept payments for digital assets such as NFTs, tokens, or other blockchain-based products.

== Installation ==

1. **Upload the Plugin**  
   - Download the plugin folder.  
   - Upload it to the `/wp-content/plugins/` directory on your WordPress site using an FTP client or the WordPress admin panel.

2. **Activate the Plugin**  
   - Go to the **Plugins** menu in your WordPress admin dashboard.  
   - Locate **Crossmint Payment Plugin** and click **Activate**.

3. **Configure the Plugin**  
   - Go to **Settings > Crossmint Payment** in your WordPress admin panel.  
   - Enter your Crossmint Client ID, which you can find in your Crossmint dashboard after signing up at [crossmint.com](https://www.crossmint.com).

4. **Add the Payment Button**  
   - Insert the shortcode `[crossmint_payment amount="10" currency="USD"]` into any page, post, or widget where you want the payment button to appear.  
   - Customize the `amount` (e.g., "10") and `currency` (e.g., "USD" or "ETH") attributes to suit your needs.

== Frequently Asked Questions ==

### Where do I get my Crossmint Client ID?
Sign up at [crossmint.com](https://www.crossmint.com), log in to your dashboard, and retrieve your Client ID from there.

### Does this plugin support NFT minting?
Not in this initial release. However, Crossmint’s API supports NFT minting, and future updates may add this functionality.

### Can I use this plugin for recurring payments?
Currently, it only supports one-time payments. Recurring payment support may be added in future versions.

== Screenshots ==

1. **Settings Page**  
   ![Settings Page](assets/screenshot-1.png)  
   The admin settings page where you input your Crossmint Client ID.

2. **Payment Button**  
   ![Payment Button](assets/screenshot-2.png)  
   Example of the payment button displayed on a WordPress page via the shortcode.

== Changelog ==

### 1.0.0
- Initial release: Basic payment button integration with Crossmint.

== Upgrade Notice ==

### 1.0.0
This is the first version of the plugin. No upgrades are available yet.

== License ==

This plugin is licensed under the GPLv2 or later. For more details, see the [license page](https://www.gnu.org/licenses/gpl-2.0.html).

== Additional Notes ==

- **Testing**: The plugin has been tested with WordPress versions up to 6.6.
- **Support**: For help, visit [yourwebsite.com/support](https://yourwebsite.com/support) or open an issue on the [GitHub repository](https://github.com/yourusername/crossmint-payment-plugin).
- **Contributions**: Contributions are welcome! Submit pull requests or report issues on GitHub.
