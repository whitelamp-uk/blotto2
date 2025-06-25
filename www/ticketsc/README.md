# Pay Now Form – Client Instances

## Overview

As our client base has grown, we’ve discovered that a single, universal payment form does **not** meet the varied needs and preferences of all clients. Each client is likely to request specific customisations—especially during onboarding.

## Approach

To solve this, we’ve adopted a **per-client instance** structure:

- The **shared PHP handler** (`ticketsc.php`) lives in each client’s project directory:  
  `/opt/crucible/www/{client_code}/www/ticketsc.php`
- **Per-client assets** (JavaScript, CSS, HTML, etc.) are stored in their own subfolder:  
  `/opt/crucible/blotto2/www/ticketsc/{client_code}/`
- Each client’s `www` directory contains a **symlink** called `ticketsc` pointing to their subfolder:  
  `/opt/crucible/www/{client_code}/www/ticketsc -> /opt/crucible/blotto2/www/ticketsc/{client_code}/`

## Benefits

- **Complete Isolation**  
  Updates or experiments for one client **do not affect** others. Each client’s form and assets are separate.

- **Safe Customisation**  
  New or onboarding clients can have their own changes and tests without breaking live forms for existing clients.

- **Shared Structure, Flexible Logic**  
  Common logic is reused, but each client can have unique assets or even override functionality as needed.

## How It Works

1. **Create a client-specific folder** for assets:  
   Example:  
   `/opt/crucible/blotto2/www/ticketsc/bwh/`

2. **Symlink the folder** into the client’s web directory:  
   Example:  
   `/opt/crucible/www/bwh/www/ticketsc -> /opt/crucible/blotto2/www/ticketsc/bwh`

3. **Reference assets** in `ticketsc.php` using the local symlink:  
   ```html
   <link rel="stylesheet" href="./ticketsc/ticketsc.css">
   <script defer src="./ticketsc/ticketsc.js"></script>

4. **Customise as needed**
   Edit or override `ticketsc.js`, `ticketsc.css`, or other files per client.
   Changes are isolated to that client only.

## Example Directory Structure

```
/opt/crucible/blotto2/www/ticketsc/
  bwh/
    ticketsc.js
    ticketsc.css
    ...
  xzy/
    ticketsc.js
    ticketsc.css
    ...

/opt/crucible/www/bwh/www/
  ticketsc    -> ../../../blotto2/www/ticketsc/bwh
  ticketsc.php
```

## Summary

This structure enables **safe, flexible, and maintainable** customisation for every client—letting us scale, onboard, and iterate without risk to other projects.

```
```
