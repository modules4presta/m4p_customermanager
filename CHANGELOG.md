# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-09-28

### Added

- A note written by the account manager and shown to the customer on their account.
- A message thread between the account manager and the customer: the employee writes from the
  customer page in the back office, the customer replies from their account.
- English and Polish translations, MIT license and the standard documentation set.

### Security

- Messages are posted through an admin controller behind the employee session on one side, and a
  front controller that requires a signed-in customer and a valid token on the other. The customer
  id always comes from the session, never from the request, so nobody can write in or read someone
  else's conversation.

## [1.0.0] - 2026-09-01

### Added

- An employee assigned as account manager, edited in the customer form, shown on the customer
  account with e-mail and phone, and listed as a sortable column in the customer grid.
