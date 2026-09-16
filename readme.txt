=== Complianz - Terms and Conditions ===
Contributors: RogierLankhorst, aahulsebos, leonwimmenhoeve, paapst, gdom3
Tags: terms, conditions, webshop, legal, terms and conditions
Requires at least: 5.0
License: GPL3
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.4.1

Configure your own Terms and Conditions specific to your service or webshop.

== Description ==
Complianz - Terms and Conditions is a stand-alone plugin from Complianz. A simple, but in-depth wizard will configure a Terms and Conditions page for your website or for those of your clients.

== Features ==
* A simple but in-depth wizard to configure the Terms & Conditions specified to your needs or the needs of your clients.
* Includes specific paragraphs for affiliate marketing, WooCommerce or Easy Digital Downloads, digital and physical goods and services, and other variables.
* Optional are sections about minimum age requirements, a return policy, accessibility policy and user created content, for example.
* A full-featured Terms & Conditions applicable to most businesses and personal endeavours, and available for editing if needed.
* Can be used stand-alone or fully integrated with the Complianz - GDPR/CCPA Cookie Consent plugin.

Are you missing anything or have suggestions? Leave an issue, or do a pull request on [GitHub](https://github.com/complianz/complianz-terms-conditions).

Check out other plugins developed by Complianz: [Complianz - GDPR/CCPA Cookie Consent](https://complianz.io/).

[Contact](https://complianz.io/support/) us if you have any questions, issues, or suggestions. Complianz - Terms & Conditions is developed by [Complianz B.V.](https://complianz.io).

= Installation =
* Go to “plugins” in your Wordpress Dashboard, and click “add new”
* Upload the downloaded .zip file and activate the plugin
* Navigate to Tools -> Terms and Conditions and follow the instructions
* If you already have Complianz GDPR/CCPA Cookie Consent installed: Please visit Complianz -> Terms and conditions.

IMPORTANT! Complianz - Terms and Conditions can help you meet compliance requirements, but you as the user must nonetheless ensure that you have all the necessary configurations in place.

== Frequently Asked Questions ==
= Knowledgebase =
Complianz maintains a continuously growing knowledgebase on privacy and legal matters on [complianz.io](https://complianz.io)
= Can I translate the Terms and Conditions? =
The pages are [fully translatable](https://complianz.io/translating-terms-conditions/), but come standard in many languages. The Terms & Conditions will default to your WordPress language. For multiple languages you can use WPML, Polylang and other translation plugins. To translate directly to your language, help us out as an editor or translate locally with Loco Translate for example.
= Can I edit the Terms and Conditions? =
You can always go back to the wizard to adjust your configuration. You can also edit the text by [unsynching with the wizard](https://complianz.io/editing-terms-conditions/) in the right colum of Gutenberg or Classic Editor.
= Can I style the Terms and Conditions? =
The Terms & Conditions have their own [CSS classes](https://complianz.io/styling-terms-conditions/), but by default will be styled by your theme's settings.
= What are Terms and Conditions =
Terms and Conditions, on the web, also known as Terms of Service or Terms of Use, is an agreement explaining the terms, rules, and guidelines to which a user must agree before entering a contractual relationship with the website owner.

== Change log ==
= 1.4.1 =
* September 16th, 2026
* Fix: repaired malformed placeholders in community translations (e.g. Czech) that could trigger a fatal error on PHP 8; a damaged translation now degrades gracefully instead of crashing the site.
* Improvement: tested and confirmed compatible with WordPress 7.1.

= 1.4.0 =
* July 27th, 2026
* New: built-in online withdrawal function for EU Directive 2023/2673, available as a Withdrawal page, a Gutenberg block, and a shortcode.
* New: the wizard now lets you choose between the Complianz-provided withdrawal form and linking to your own withdrawal function; the generated Terms & Conditions text reflects your choice.
* New: withdrawal requests are sent to the merchant by email (with the consumer as Reply-To) and the consumer receives an acknowledgement of receipt; an on-screen confirmation is shown after submitting.
* Improvement: the withdrawal form is accessible and localized, with honeypot, minimum-time and rate-limit anti-abuse protection and a cache-safe nonce.
* Improvement: removed the legacy withdrawal-form PDF generation and cleaned up stale generated withdrawal PDFs on upgrade. The Terms & Conditions document download is unaffected.

= 1.3.1 =
* June 19th, 2026
* Improvement: simplified the withdrawal section (EU Directive 2023/2673) - users now provide a link to their own withdrawal function, which is required.
* Improvement: updated the right of withdrawal text and removed references to the model/paper withdrawal form.
* Fix: required fields with a display condition are now correctly enforced when their condition applies.
* Improvement: tested and confirmed compatible with WordPress 7.0.
* Fix: replaced a deprecated jQuery .hover() call in the admin script, removing a jQuery Migrate deprecation notice on WordPress 7.0 (jQuery 3.7).

= 1.3.0 =
* March 24th, 2026
* Improvement: raised minimum PHP requirement to 7.4.
* Fix: updated Gutenberg block to API version 3 for WordPress 6.9+ iframe editor compatibility.
* Fix: security hardening in admin JavaScript (DOM XSS).
* Improvement: upgraded mpdf to v8.2.7.
* Fix: deferred translation loading for WordPress 6.7 compatibility.
* Fix: code quality and static analysis improvements.

= 1.2.8 =
* May 7th 2024
* Improvement: changed email obfuscation to use core WordPress functionality

= 1.2.7 =
* September 26th 2023
* Improvement: change 'countries' into 'jurisdiction'

= 1.2.6 =
* September 5th 2023
* Fix: Block Editor dropping styles in unsynced mode
* Improvement: Custom Withdrawal Form default on 'no'

= 1.2.5 =
* Improvement: WordPress tested up to 6.3
* Improvement: MPDF library update for PHP version support

= 1.2.4 =
* Improvement: Bitnami compatibility
* Improvement: compatibility with Loco Translate

= 1.2.3 =
* New: Adjustment to Burst Statistics upgrade recommendation

= 1.2.2 =
* Fix: Hyperlinks

= 1.2.0 =
* New: branding update

= 1.1.5 =
* Fix: when selecting another communications language in the wizard, then the current active language, both languages would show up instead of just the selected
* Improvement: Cookie Policy en Privacy Statement URLs translatable
* Improvement: own withdrawal form

= 1.1.4 =
* Fix: inconsistent result when using 16 as minimum age
* Fix: support for symlinked configurations
* Improvement: small textual changes

= 1.1.3 =
* Minor bug fixes
* Tested up to 6.1

= 1.1.2 =
* Fix: not declared variable in combination with Complianz integration

= 1.1.1 =
* Requires PHP version upgraded to 7.2
* Fix: shortcode suggestion on dashboard

== Upgrade notice ==
* Please create a back-up before updating.

== Screenshots ==
1. The wizard. Generate your Terms & Conditions with the wizard from Complianz.
