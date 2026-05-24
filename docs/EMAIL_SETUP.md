# Email setup for joreption.com

_Goal: be able to **receive** mail at addresses like `orders@joreption.com` and let the
platform **send** mail (password resets, order emails) from that address._

As of now the domain has **no MX records and no email hosting** — so this is a one-time
setup. DNS for `joreption.com` is managed at **Namecheap**, and the website is on the
Contabo VPS (`178.18.244.125`). Email hosting is a **separate service** from the website —
adding it does **not** affect the site.

---

## Decide: full mailboxes vs. send-only

| | Mailboxes (recommended) | Send-only relay |
|---|---|---|
| Receive mail at `@joreption.com` | ✅ yes | ❌ no |
| Send from the platform (SMTP) | ✅ yes | ✅ yes |
| Cost | low (paid) | free tier |
| Best for | a real business inbox | just transactional emails |

This guide does the **mailbox** route (Option A). Option B (relay) is at the bottom as a
free alternative if you only ever need to *send*.

---

## Option A — Namecheap Private Email (mailboxes + SMTP)

Easiest because the domain already lives at Namecheap, so the MX records auto-configure.

### 1. Buy Private Email
1. Log in to **namecheap.com** → **Domain List** → find **joreption.com**.
2. Open the **"Private Email"** product (or Namecheap → Apps → Private Email) and buy a
   plan for `joreption.com` (the Starter plan / 1 mailbox is enough to begin).

### 2. Let Namecheap add the DNS records
- Because the domain's DNS is managed by Namecheap, it will offer to **auto-add** the
  MX + SPF + DKIM records. Accept that.
- If you ever need them manually (Domain → **Advanced DNS**):
  - **MX** `@` → `mx1.privateemail.com` (priority **10**)
  - **MX** `@` → `mx2.privateemail.com` (priority **10**)
  - **TXT (SPF)** `@` → `v=spf1 include:spf.privateemail.com ~all`
  - **DKIM** — Namecheap's Private Email dashboard shows the exact `default._domainkey`
    TXT value to add (turn on DKIM in the dashboard).
- DNS changes take anywhere from minutes to a couple of hours to take effect.

### 3. Create a mailbox
- In the Private Email dashboard, create a mailbox such as **`orders@joreption.com`**
  (or `info@`, `noreply@`) and set a password. Write the password down — you'll paste it
  into the platform.

### 4. SMTP settings (to plug into the platform)
| Field | Value |
|---|---|
| Host | `mail.privateemail.com` |
| Port | `465` (SSL) — or `587` (TLS) |
| Username | the full email, e.g. `orders@joreption.com` |
| Password | that mailbox's password |
| Encryption | **SSL** for 465, **TLS** for 587 |
| From address | the same mailbox, e.g. `orders@joreption.com` |

---

## Plug it into the platform

1. Admin → **Notifications** → **Email** section.
2. Turn on **Enable email**.
3. Fill **From name** (e.g. `Joreption`) and **From address** (your mailbox).
4. Enter **SMTP host / port / username / password / encryption** from the table above.
5. Click **Save**, then the **"Send test email"** button (top of the page).
6. Check the inbox you sent the test to. If it arrives, you're done — password-reset
   emails will now work too.

> If the test fails, the error message is shown. Most common causes: wrong port/encryption
> pair (use 465+SSL **or** 587+TLS), or a typo in the username/password.

---

## Option B — Resend (free, send-only)

If you only need to *send* (no inbox), this is free and quick:
1. Create an account at **resend.com**, add the domain `joreption.com`.
2. Add the DNS records Resend shows you, at Namecheap → **Advanced DNS** (an SPF TXT, a
   DKIM TXT, and usually a return-path record). Wait for Resend to show "Verified".
3. SMTP settings: Host `smtp.resend.com`, Port `465` (SSL) or `587` (TLS),
   Username `resend`, Password = your **Resend API key**. From: any `@joreption.com`.
4. Plug into the platform exactly as above.

---

## When you're ready

Once you've added the DNS records, tell me — I can **check that the MX / SPF / DKIM
records have propagated**, then help you run the test send from the admin and confirm
password-reset emails work end to end.
