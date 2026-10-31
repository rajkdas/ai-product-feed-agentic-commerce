=== AI Product Feed & Agentic Commerce for WooCommerce – ChatGPT, ACP, UCP & LLMs.txt ===
Contributors: rajkdas
Tags: product feed, ai, agentic commerce, shopping feed, woocommerce, chatgpt, llms.txt, structured data, acp, ucp, product feed manager
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 7.6
WC tested up to: 11.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI product feeds for WooCommerce: ChatGPT Shopping (OpenAI ACP) JSONL/CSV, /llms.txt, UCP discovery and AI Readiness Scores. Unlimited, free.

== Description ==

AI Product Feed & Agentic Commerce is a free WooCommerce plugin that makes your catalog discoverable, recommendable, and buyable across AI shopping channels. It generates OpenAI ACP product feeds built to OpenAI's published Commerce Feed Reference for ChatGPT Shopping (JSONL & CSV), publishes the /llms.txt and /llms-full.txt catalog files that Perplexity, Claude and other assistants love to quote, exposes ACP & UCP discovery manifests for Google AI Mode, and audits every product with a 0-100 AI Readiness Score. No coding required - your first feed is about 10 minutes away.

**This is the AI-era shopping feed: built for ChatGPT Shopping, Google AI Mode, Perplexity, and Claude.** When the [900M+ people who ask ChatGPT every week](https://searchengineland.com/guide/chatgpt-stats) say *"Find me waterproof trail running shoes in size 10 under $100 that ship in 2 days"*, AI agents do not browse bloated HTML pages. They ingest structured **OpenAI Commerce Product Feeds (JSONL/CSV)**, query **Agentic Commerce Protocol (ACP)** and **Universal Commerce Protocol (UCP)** manifests, and read clean **/llms.txt** catalog indexes.

If your catalog is not in that layer, it is not in the answer.

**AI Product Feed & Agentic Commerce for WooCommerce is the complete, self-hosted AI commerce suite.** No external SaaS middleman, no API keys, no 25-product trial caps, and zero performance impact on your storefront.

AI Product Feed & Agentic Commerce is an independent plugin: not affiliated with, endorsed by, or sponsored by OpenAI, Google, Anthropic, Perplexity, Shopify, or WooCommerce. All product names and trademarks belong to their respective owners and are used only to describe compatible protocols and destinations.

== The Hidden Cost of Being Invisible to AI ==

AI-assisted shopping has stopped being a prediction. Adobe Analytics measured a [4,700% year-over-year surge](https://business.adobe.com/blog/5-ways-ai-will-change-black-friday) in LLM-driven traffic to US retail sites, and during the 2025 holiday season [AI-sourced traffic to retailers rose 693%](https://business.adobe.com/resources/holiday-shopping-report.html) over the previous year. In an Adobe survey, 52% of U.S. consumers said they planned to shop with generative AI - for product research, recommendations, and gift lists.

Meanwhile, here is what that looks like for a WooCommerce store that is not ready:

- Your products never show up in AI answers, while competitors who publish feeds get recommended instead
- Your beautifully designed product pages are noise to an agent that wants item_id, price, and availability in one clean row
- Missing brands and invalid GTINs make feed rows untrustworthy, so assistants quietly skip them
- WebP/AVIF images get silently dropped from ChatGPT Shopping carousels
- Every week you wait, early merchants compound recommendation history you will have to catch up to

The feed is the new storefront. If an AI agent cannot read your catalog, you are closed whenever shoppers ask AI.

== What If AI Could Recommend Your Products Tomorrow? ==

Imagine a shopper asking ChatGPT for exactly what you sell - and your product comes back with the right price, the right stock status, and a compliant image. Perplexity quotes your shipping and return terms verbatim from /llms-commerce.txt. Google AI Mode discovers your catalog through your UCP manifest. And your own Products screen shows a 0-100 readiness badge on every item, so you always know what to fix next.

That is not a prototype. That is what happens when your catalog speaks machine - and everything you need to get there ships free in this plugin.

[Documentation](https://github.com/rajkdas/ai-product-feed-agentic-commerce#readme) | [Installation](#installation) | [FAQ](#faq) | [Support](https://wordpress.org/support/plugin/ai-product-feed-agentic-commerce/)

== 3 Steps. 10 Minutes. Done. ==

1. **Run the setup wizard** - Map your Brand and GTIN sources, set your seller name and return policy URL. The 1-click field mapper finds identifiers hiding in WooCommerce core, Yoast SEO, Rank Math, Germanized, or any custom meta key.
2. **Publish AI discovery** - Your JSONL/CSV feeds, the /llms.txt family, and ACP/UCP manifests build themselves automatically, on your schedule.
3. **Submit your feed URL** - Copy the hosted JSONL/CSV URL into OpenAI merchant onboarding for ChatGPT Shopping, then let the AI Readiness Score show you which products to polish.

That is it. No XML editing. No spreadsheet gymnastics. No SaaS dashboard. No developer.

== Every AI Channel. One Plugin. ==

- **ChatGPT Shopping & OpenAI Ads** - An OpenAI ACP product feed (JSONL & CSV) built to OpenAI's published Commerce Feed Reference, with variations, identifiers, and policy fields
- **Google AI Mode & Gemini** - A /.well-known/ucp discovery manifest plus a UCP catalog search REST endpoint
- **Perplexity Shopping, Claude & every AI assistant** - /llms.txt, /llms-full.txt, and /llms-commerce.txt with live prices, stock, and variation matrices
- **Search engines** - Product JSON-LD enriched with hasMerchantReturnPolicy, shippingDetails, and FAQPage markup
- **The 8 AI crawlers that matter** - GPTBot, OAI-SearchBot, ChatGPT-User, PerplexityBot, ClaudeBot, Google-Extended, CCBot, and Amazonbot - monitored so nothing blocks them

== Built for Stores That Want to Be Recommended ==

AI assistants favor product data they can trust. Feed rows with complete identity - brand, GTIN, clean images, real descriptions - get matched, quoted, and bought. Incomplete rows get skipped. So this plugin does not just export your catalog; it helps you fix it first.

**Spec-perfect OpenAI feeds, automatically.** Every purchasable variation becomes its own feed row, linked to the parent group_id with a consistent variant_dict (color, size, material). The WebP/AVIF-to-JPEG/PNG auto-resolver keeps image_url compliant so ChatGPT carousels never skip your products.

**AI Readiness Score (0-100).** A deterministic 7-point audit - required fields, brand, image format, description depth, GTIN/MPN identifiers, attributes, FAQ coverage - rendered as colored badges in Products > All Products, grouped into AI Ready / Needs Polish / AI Blocked, with one-click bulk recalculation.

**1-click product identity mapping.** Pull GTIN, UPC, EAN, ISBN, MPN, and Brand from WooCommerce core, Yoast, Rank Math, Germanized, or any custom meta key, with Modulo-10 GTIN check-digit validation and a store-wide fallback brand.

**The /llms.txt family, e-commerce flavored.** /llms.txt (short store index), /llms-full.txt (every product with SKU, price, stock, and deep links), /llms-commerce.txt (shipping, returns, payments). Detects Yoast SEO or Rank Math and extends their output via native filters instead of duplicating files.

**Dual-protocol discovery.** Compliant /.well-known/acp.json (OpenAI ACP) and /.well-known/ucp (Google & Shopify's UCP) manifests with automatic merchant logo resolution - Customizer logo, Site Icon fallback, or manual override.

**Per-product control.** A dedicated metabox on every product: readiness checks, brand and availability overrides, condition/gender/age-group fields, an exclude-from-all-AI-channels toggle, and conversational Q&A that assistants quote verbatim and search engines see as FAQPage JSON-LD.

**AI crawler guard.** Scans your effective robots.txt, live-probes /llms.txt and your feed URL, and shows per-bot status for the eight major AI crawlers - so a security plugin, SEO plugin, or firewall never silently costs you visibility.

**Zero storefront impact.** Feeds build on their own schedule - WordPress cron, your own cron URL, or a manual button - write atomically as static files, and serve straight from disk: no front-end scripts or styles, no storefront queries, no third-party calls on shopper page loads. Generation never runs inside a shopper's request.

**Self-hosted, end to end.** Everything is generated on your own server under wp-content/uploads/acpfw-feeds/ with directory listing disabled. The plugin never sends your catalog to a third-party service - its only HTTP calls are loopback checks to your own site - and an optional clean uninstall removes every stored trace. The published feed and discovery URLs are public by design: that is exactly how AI agents find you.

**Developer friendly.** WordPress hooks and filters throughout (like acpfw_merchant_logo), catalog reads through WooCommerce CRUD APIs, and declared HPOS + Cart/Checkout Blocks compatibility.

== Installation ==

**Under 5 minutes. No technical skills required.**

= From WordPress (Recommended) =

1. Go to **Plugins > Add New**
2. Search for "AI Product Feed & Agentic Commerce"
3. Click **Install Now**, then **Activate**

= Manual Installation =

1. Upload the `ai-product-feed-agentic-commerce` folder to `/wp-content/plugins/`
2. Activate from the **Plugins** page

= After Activation =

1. Complete the **3-step setup wizard**: map Brand/GTIN sources, set seller name and return policy URL, generate your first feed
2. Copy your hosted JSONL/CSV feed URL from **WooCommerce > Agentic AI Feed** and submit it through OpenAI merchant onboarding
3. Review your **AI Readiness Score** badges under **Products > All Products**

Done. Your catalog is now speaking to every major AI channel.

== Frequently Asked Questions ==

= How do I get my WooCommerce products into ChatGPT Shopping? =

Install the plugin, run the 3-step setup wizard, then copy your hosted JSONL feed URL from **WooCommerce > Agentic AI Feed** and submit it through OpenAI's merchant onboarding for ChatGPT Shopping. ChatGPT ingests the feed and starts surfacing your products when shoppers ask matching questions, and OpenAI charges no fees on purchases that start in ChatGPT. The feed rebuilds automatically on your chosen schedule, so prices, stock and new products stay current without manual exports.

= Does the free version require an external account, SaaS service, or API key? =

No. All feed generation (`.jsonl`, `.csv`, `/llms.txt`, `/llms-full.txt`), discovery manifests (`/.well-known/acp.json`, `/.well-known/ucp`), and AI Readiness Scores run 100% locally on your WordPress server - no external account, no API key, no product limits, and no SaaS middleman.

= What is the Agentic Commerce Protocol (ACP)? =

ACP is OpenAI's open standard (built with Stripe) that connects merchants to shoppers inside ChatGPT: it lets ChatGPT ingest structured catalog data, understand your inventory, and surface relevant products in shopping results. This plugin publishes a compliant `/.well-known/acp.json` discovery manifest plus a spec-checked JSONL/CSV product feed, so a WooCommerce store can join the protocol without writing a line of code.

= What is /llms.txt and why does my WooCommerce store need one? =

`/llms.txt` is an emerging Markdown standard (llmstxt.org) that gives AI models a clean, token-efficient map of your site instead of forcing them to parse bloated HTML. This plugin publishes an e-commerce-flavored trio: `/llms.txt` (short store index), `/llms-full.txt` (every product with live prices, stock and buy links) and `/llms-commerce.txt` (shipping, returns and payments guide). If Yoast SEO or Rank Math is already publishing `/llms.txt`, the plugin extends their output via native filters instead of duplicating the file.

= What is the difference between /llms.txt, /llms-full.txt and /llms-commerce.txt? =

`/llms.txt` is the concise index AI assistants read first - your store summary plus section links. `/llms-full.txt` carries the full catalog: every purchasable variation with SKU, current price, availability, variation matrix and a direct product link. `/llms-commerce.txt` answers the "how do I buy" questions - shipping options, return policy and accepted payment methods - so agents can quote accurate terms verbatim.

= What is the Universal Commerce Protocol (UCP)? =

UCP is the open agentic-commerce standard co-developed by Google and Shopify for AI-led buying flows such as Google AI Mode and Gemini shopping experiences. The plugin publishes a `/.well-known/ucp` discovery manifest and a UCP catalog search REST endpoint, so your WooCommerce catalog is discoverable on the Google side of agentic commerce as well as through OpenAI's ACP.

= Which AI platforms and shopping agents does this cover? =

ChatGPT Shopping and OpenAI Ads through the ACP feed; Google AI Mode (Gemini) through UCP discovery; Perplexity Shopping, Claude and other assistants through the `/llms.txt` family and open crawler access. The built-in crawler monitor verifies that GPTBot, OAI-SearchBot, ChatGPT-User, PerplexityBot, ClaudeBot, Google-Extended, CCBot and Amazonbot can all reach your store.

= Do my products need GTIN, MPN or brand data to be recommended by AI? =

Complete product identity makes feed rows trustworthier and helps AI channels match your items unambiguously - missing GTINs are among the most common product-feed errors everywhere. The 1-click field mapper pulls GTIN, UPC, EAN, ISBN, MPN and Brand from WooCommerce core, Yoast, Rank Math, Germanized or any custom meta key, validates GTIN check digits with Modulo-10, and can apply a store-wide fallback brand. The AI Readiness Score tells you exactly which products are missing what.

= Why does ChatGPT reject my product images? =

OpenAI's Commerce Feed spec requires `image_url` to be a direct JPEG or PNG file. If your store serves `.webp` or `.avif` images, ChatGPT Shopping carousels may drop them. Our plugin automatically resolves the underlying JPEG/PNG image for every feed row.

= What is the AI Readiness Score and how is it calculated? =

A deterministic 0-100 score from a 7-point audit: required feed fields, brand, image format, description depth, GTIN/MPN identifiers, product attributes, and FAQ coverage. Scores render as colored badges in **Products > All Products**, the dashboard groups your catalog into AI Ready / Needs Polish / AI Blocked, and one click recalculates the whole catalog. Because the audit is deterministic, the same product data always produces the same score.

= Will this conflict with Yoast SEO, Rank Math, or WooCommerce Core ACP? =

No. When Yoast SEO or Rank Math is active it owns /llms.txt: the plugin detects it and steps aside, appending its commerce appendix to their output through the native wpseo_llmstxt_content and rank_math/llmstxt/content filters instead of duplicating the file. Product JSON-LD is enriched the same way through their schema filters (wpseo_schema_graph, rank_math/schema/json), so nothing is ever duplicated or fought over.

= Does it support variable products with multiple sizes, colors or materials? =

Yes - and this is where many feed plugins break. Every purchasable variation becomes its own feed row built to OpenAI's published Commerce Feed Reference, linked to the parent `group_id`, with a `variant_dict` whose options always match the row's top-level attributes and deep-link URL, unique attribute combinations only (duplicates are never exported), and per-variation deep links in `/llms-full.txt`.

= How do I hide a single product from AI channels? =

Open the product, find the **AI Product Feed (Agentic Commerce)** box, and tick "Exclude this product from all AI feeds and llms.txt output". The product disappears from the JSONL/CSV feeds and llms-full.txt, and its list badge switches to *Excluded*.

= Where do the Conversational Q&A pairs I add in the product metabox show up? =

They appear in `/llms-full.txt` inside the product block (as "FAQ: question / Answer: ..." lines), so AI assistants can quote them verbatim. They also power the FAQ check of the AI Readiness Score (2+ pairs = full points) and are published as FAQPage JSON-LD for search engines. The OpenAI JSONL/CSV feed intentionally does not carry them: the OpenAI Commerce Feed spec has no FAQ field, and unknown fields can make a feed row invalid.

= Can I change how often the feeds rebuild? =

Yes. Open **WooCommerce > Agentic AI Feed > Settings** and pick Hourly, Twice a day, Once a day (default), Once a week, or External cron / manual only. There is also a toggle to rebuild automatically whenever product data changes (batched, about a minute after the save), and a secret rebuild URL you can call from a real cron job or uptime monitor.

= Will this slow down my store? =

No. Feeds are generated on a schedule (WordPress cron or your own cron URL), written atomically as static files, and served straight from disk - there are no storefront queries, no front-end scripts or styles, and no third-party calls on shopper page loads. Large catalogs are scanned in pages of 100 products so memory stays flat, and generation never runs inside a shopper's request.

= How many products can it handle? =

Unlimited. The free version has no artificial caps - no 25-product trials, no per-row pricing. Because output is a static file, serving a 10,000-row feed costs exactly the same as serving a 10-row one, and generation time scales linearly with catalog size while running entirely in the background.

= How is this different from a Google Shopping feed plugin? =

Google Shopping feed plugins export XML for Google Merchant Center - a different protocol for a different channel. This plugin targets the AI-native layer: OpenAI's ACP JSONL/CSV spec, UCP discovery for Google, the `/llms.txt` family for LLMs, plus an AI Readiness Score, FAQPage JSON-LD and AI crawler monitoring that shopping-feed plugins do not offer. And it is fully self-hosted: no SaaS dashboard, no account, no tiered pricing.

= Will AI crawlers be blocked by my robots.txt, security plugin or firewall? =

The AI Bot Allowlist & Crawler Monitor scans your effective robots.txt output for the eight major AI crawlers, live-probes `/llms.txt` and your feed URL, and shows exactly which bots are blocked so you can fix the cause before it costs you visibility. WordPress-level blocks (from SEO or security plugins) are detected directly; hosting firewalls and CDNs sit outside WordPress, and the monitor tells you when a check cannot see that far.

= Which prices are exported - with or without tax? =

The same amounts your storefront displays. The plugin asks WooCommerce for the display price (wc_get_price_to_display), so your tax settings - "prices entered with tax" and "display prices in the shop" - decide whether feeds carry tax-inclusive or tax-exclusive amounts. Sale prices are exported the same way next to the regular price.

= Is WordPress multisite supported? =

Activate it per site: the plugin is single-site focused, and network activation does not propagate settings, schedules or rewrite rules to subsites. When the optional clean uninstall runs, it does remove data from every site in the network.

= I already have a physical /llms.txt file in my web root. Which one wins? =

The physical file - web servers serve it directly before WordPress runs. Remove or rename it and re-save Settings > Permalinks if you want this plugin to manage /llms.txt. Yoast SEO and Rank Math users need nothing: the plugin hands /llms.txt over to them and merges its commerce appendix into their output.

= Is any store or product data sent to a third-party server? =

No. Feeds, llms.txt files, manifests and scores are generated and stored on your own server under `wp-content/uploads/acpfw-feeds/` (directory listing disabled), and the plugin makes no calls to third-party services carrying your catalog data - its only HTTP requests are loopback checks to your own site during the built-in diagnostics. Deactivating stops generation; the optional clean uninstall removes every stored trace. Note that the published feed and discovery URLs are intentionally public: AI agents must be able to read them, exactly like your product pages.

== Screenshots ==

1. Agentic AI Feed dashboard - 3-step setup checklist, hosted JSONL/CSV feed URLs and one-click regeneration.
2. AI discovery endpoints - /llms.txt, /llms-full.txt, /llms-commerce.txt, ACP and UCP manifests, with the built-in AI crawler monitor.
3. Catalog AI Readiness dashboard - every product scored 0-100 and grouped into AI Ready, Needs Polish and AI Blocked.
4. Settings - brand, GTIN and MPN field mapping, seller profile, rebuild schedule and the external cron URL.
5. AI Readiness column in Products > All Products - per-product badges, score-band filter and bulk recalculation.
6. Per-product AI feed metabox - readiness checks, brand and availability overrides and conversational Q&A.

== Changelog ==

= 1.0.0 =
* Initial release: OpenAI ACP & ChatGPT Shopping product feeds (JSONL/CSV), /llms.txt + /llms-full.txt + /llms-commerce.txt generators, dual-protocol discovery manifests (/.well-known/acp.json & /.well-known/ucp), UCP catalog search REST endpoint, and daily automatic rebuilds.
* Smart variable product flattening: one feed row per purchasable variation, unique option combinations only, parent group_id linking, and per-variation add-to-cart deep links in llms-full.txt.
* Spec-compliant output: WebP/AVIF to JPEG/PNG auto-resolver with right-sized image selection, HTML-entity decoded titles/descriptions, short-description fallback, and plain UTF-8 output with no BOM, so strict CSV and JSONL parsers read every header and row.
* Deterministic AI Readiness Score (0-100): 7-point audit with GTIN Modulo-10 validation, products-list badge column with score-band filter and bulk recalculation, and a per-product AI feed metabox (brand, availability, condition, gender and age group overrides, exclude toggle, conversational Q&A surfaced in llms-full.txt and FAQPage JSON-LD).
* Product JSON-LD enrichment: injects hasMerchantReturnPolicy and shippingDetails into Product schema and publishes FAQPage JSON-LD, extending Yoast SEO and Rank Math output via native filters when active.
* 1-click brand & GTIN field mapper, AI bot allowlist & crawler monitor, 3-step setup checklist, configurable rebuild schedule (hourly / twice daily / daily / weekly / external cron with secret URL, plus optional rebuild-on-product-change), and opt-in clean uninstall.
* Merchant logo resolution for discovery manifests: Customizer Site Logo preferred, Site Icon fallback, manual override URL in settings, all filterable via acpfw_merchant_logo.
* Fix - Variant option sets are consistent end to end: variant_dict includes every variation attribute (including custom ones like logo), the top-level color/size fields and the deep-link URL describe the same options, and identical option combinations inside one product are exported once. llms-full.txt variation tables use the same rules, list every option, and show the same variant counts as the feeds.
* Fix - The CSV feed no longer starts with a UTF-8 BOM; strict feed parsers would otherwise read the first column as a byte-marked item_id and reject every row.
* Fix - The UCP discovery manifest is date-versioned per the UCP conformance spec and exposes the canonical top-level ucp object.
* Fix - Anonymous requests to admin-ajax.php or admin-post.php can no longer trigger catalog builds; building requires a real capability, WP-Cron, or WP-CLI.
* Fix - Hidden, search-only and password-protected products are no longer exported to the feeds, llms-full.txt or the catalog search API.
* Fix - The public commerce guide no longer publishes the WordPress admin email address.
* Fix - Tax-aware pricing: exports now carry the same display prices the storefront shows (follows your WooCommerce tax settings).
* Fix - The robots.txt crawler monitor was rewritten with correct robots semantics: grouped user-agents, empty Disallow values, the "*" group, Allow rules and wildcards.
* Fix - A rebuild lock prevents concurrent generations; external cron rebuilds run through Action Scheduler; bulk readiness scoring continues in background chunks on very large catalogs.
* Fix - The catalog search endpoint caches only empty-query browsing, is rate-limited, and can no longer trigger image file generation.
* Fix - Discovery fallback URLs now resolve on subdirectory (non-root) installs.
* Fix - Generated files always use the site default locale, never the locale of the triggering user.
* Added - Yoast SEO and Rank Math now fully own /llms.txt when active; the plugin merges its commerce appendix into their output.
* Added - The external cron token can be sent as an X-ACPFW-Token header or POST field instead of a URL parameter.
* Added - New acpfw_client_ip filter lets stores behind a CDN or reverse proxy pass the real client IP to the search rate limiter.
* Changed - Requires WordPress 6.5 or newer.
* Documentation: FAQ covering the researched AI-commerce questions (ChatGPT Shopping submission, ACP/UCP, llms.txt family, GTIN and brand requirements, performance, privacy) and six annotated admin screenshots added.
