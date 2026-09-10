# فكرة المشروع
تطبيق لتتبع المصاريف مدعوم بالذكاءالاصطناعي 

# Project Overview
- **Product:** AI-powered expense tracking web application
- **Target users:** Individuals
- **Platform:** Web app only (responsive, browser-based)
- **Languages:** Arabic & English (bilingual with language switcher)
- **Monetization:** Free
- **Timeline:** 1 month (MVP)

# Answers Summary (20 Questions)

| # | Question | Answer |
|---|----------|--------|
| Q1 | Target audience | Individuals |
| Q2 | Platform | Web app only (no mobile app) |
| Q3 | UI languages | Arabic  |
| Q4 | Monetization | Free |
| Q5 | Timeline | 1 month |
| Q6 | Add expenses | Manual entry |
| Q7 | Categories | Fixed predefined list |
| Q8 | Budgeting | Monthly budgets per category |
| Q9 | Reports | Monthly summary |
| Q10 | Currency | SAR only (single currency) |
| Q11 | AI features | Agentic AI chat assistant |
| Q12 | AI provider | To be decided (requirements phase) |
| Q13 | Data storage | Cloud sync |
| Q14 | User accounts | Required |
| Q15 | Offline support | Online only |
| Q16 | Auth methods | Email + password |
| Q17 | Tech stack | To be decided (requirements phase) |
| Q18 | Income tracking | Yes - income & expenses |
| Q19 | Notifications | Not applicable (web only) |
| Q20 | Data export | Yes (CSV/PDF) - required for AI assistant |

# المتطلبات وترتيبها moSCoW Style 

## Must Have (MVP - Month 1-2)
- [ ] User registration & login (email + password)
- [ ]  Arabic RTL UI
- [ ] Manual expense entry (amount, category, date, description)
- [ ] Manual income entry (amount, source, date)
- [ ] Fixed predefined expense categories (Food, Transport, Bills, Health, Entertainment, Shopping, Education, Other)
- [ ] Fixed predefined income categories (Salary, Freelance, Investment, Gift, Other)
- [ ] Monthly budget setting per category (SAR)
- [ ] Monthly summary report (total income vs total expenses, net balance)
- [ ] Category breakdown view (spending per category)
- [ ] Cloud data storage with user account sync
- [ ] Responsive web design (desktop + mobile browser)
- [ ] Data export (CSV / PDF) - for AI assistant actions
- [ ] **AI Assistant - Priority V:**
  - [ ] Answer finance Q&A (e.g., "How much did I spend on food last month?")
  - [ ] Add transactions by chat (e.g., "Add 45 SAR for groceries")
  - [ ] Give budgeting advice (e.g., "You're over budget on dining out")
  - [ ] Detect anomalies (e.g., "This 500 SAR charge looks unusual")
  - [ ] Trigger actions (e.g., "Create a new budget category", "Export my data")

## Should Have (Post-MVP)
- [ ] Budget alert indicators (visual warnings when nearing limits)
- [ ] Spending trends over time (month-over-month comparison)
- [ ] Recurring expenses (auto-repeating transactions)
- [ ] Search & filter transactions
- [ ] Edit & delete transactions

## Could Have (Future Releases)
- [ ] Push notifications (budget alerts via browser)
- [ ] Multiple currency support
- [ ] Receipt scanning (OCR)
- [ ] Bank statement import
- [ ] Dark mode

## Won't Have (This Version)
- [ ] Native mobile app (iOS/Android)
- [ ] Offline mode
- [ ] Social / shared budgets
- [ ] Multi-user household accounts
- [ ] Payment integration
- [ ] bilangual app 


# أختيار التقنيات
-laravel 
-Inertia (laravel svelte template)
-TailwindCSS
