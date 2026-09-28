# M4P Account Manager for PrestaShop 8 & 9

**Give every wholesale customer a name and a face on their account — and a place to write back, without a ticket system.**

> **Meta description (148 chars):** Assign a sales representative to a PrestaShop customer, show their contact details on the account and let the two exchange messages. Free MIT module.

---

## Why a name beats a contact form

A company that buys from you regularly does not want to write to `info@`. They want the person who
knows their order history, their prices and their delivery address:

- **Faster answers** — the customer writes to a named person instead of a shared inbox
- **Visible on the account** — name, e-mail and phone, not buried in an old e-mail thread
- **A note where it is read** — payment terms, an agreed discount, anything worth repeating
- **Conversation stays with the account** — the next person who takes over sees all of it

## What the module does

In the customer edit form in the back office you pick the employee who looks after that customer
and, if you want, write a note for them. The customer sees the manager's details and that note on
their account, along with a thread they can reply to. The employee sees the same thread on the
customer page in the back office.

### Key features

- **Assigned in the customer form** — one field, next to everything else about that customer
- **Sortable column in the customer list** — see and filter by who looks after whom
- **Phone number per employee** — PrestaShop has no field for it, so the module adds one
- **Note for the customer** — shown on the account under the manager's details
- **Two-way messages** — the employee writes from the back office, the customer from their account
- **In the top menu** — a compact bar with the manager's name for signed-in customers

### What it does not do

The module is not a helpdesk: no ticket statuses, no assignment rules, no notifications by e-mail,
no attachments. It is a name, a note and a thread.

## Compatibility

| | |
|---|---|
| PrestaShop | 1.7.6 – 9.x |
| PHP | 7.4+ |
| Requirements | none |
| Multistore | Assignments and messages are shared across shops |
| Themes | The account block needs `displayCustomerAccount`, the bar needs `displayNav2` |

The module performs no core overrides and does not replace any back-office template — it uses the
Symfony form and grid hooks PrestaShop provides. It creates three tables,
`m4p_customer_manager`, `m4p_employee_phone` and `m4p_customer_message`, and drops them on uninstall.

## Installation

1. Upload and install the module from **Modules → Module Manager**.
2. Open the module configuration and enter phone numbers for the employees who will be managers.
3. Open a customer in **Customers**, pick the account manager, optionally write a note, and save.
4. Sign in as that customer — the manager, the note and the message form are on the account page.

## Configuration options

| Where | What |
|---|---|
| **Module configuration** | A phone number per employee. |
| **Customer form** | The account manager and the note shown to that customer. |
| **Customer page** | The message thread, where the employee writes to the customer. |

## Frequently asked questions

**Who can read a conversation?**
The customer it belongs to, and any employee who can open that customer in the back office. The
customer id comes from the session, so no account can reach another's thread.

**Does the customer get an e-mail when I write to them?**
No. The message waits on their account. Notifications would need a mail template and a sending
policy, which is deliberately out of scope.

**Can one employee look after many customers?**
Yes, and the customer list can be sorted and filtered by manager to see who has how many.

**What happens when I delete the employee?**
The assignment stays in the table but resolves to nobody, so the block disappears from the account.
Reassign the customer to someone else.

**What happens to messages when I uninstall the module?**
All three tables are dropped, so assignments, phone numbers and conversations are removed.

---

**Keywords:** PrestaShop account manager, sales representative, B2B customer care, customer messages,
assigned advisor, wholesale support.

## License

MIT — see [LICENSE](LICENSE). Free to use commercially, fork and modify; keep the copyright notice.

## Contributing

Bug reports and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). For security
issues, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

---

Built by [Nice Code](https://nice-code.com/pl/produkty/sklep-b2b-prestashop) — we build B2B stores on PrestaShop.

© Nice Code sp. z o.o. (Modules4Presta) — released under the MIT license.
