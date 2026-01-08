# truetrack
True track My Money is a solution to help the tracking of the personal finances, using accounting techniches to bring the accurate insights about the spendings. 

Follow bellow some of the requirements of the solution:

Functional requirements
- Identify if bank accounts are at risk of going negative (paying interest)
- Identify how much was received and spent in each period, and if there was a "profit" (revenue minus expenses)
* Project the month's income and expenses, based on revenue and expense expectations
- Identify how much money was invested in the period
- Identify the credit card bill amount to know if there will be enough money to pay the bill
- Identify where/on what the money is being spent (there may be excesses)
- Identify how much the investments are yielding

Below are some techniques/structures used to achieve the above objectives:

* All expenses and revenues are recorded as entries, assigned to "Revenue/Expense Categories", and also to "Transaction Accounts" (which can be bank accounts, credit cards, personal wallets, or even transit accounts). Transactions can also be assigned to "Tags," which are a way to group specific transactions for future management/monitoring.

Finally, transactions are also identified by the dates they occurred and the dates they were actually settled (paid/received).

* Credit card accounts need to be zeroed out monthly against another transaction account (meaning payment of the credit card bill).

* Bank statements (bank account type accounts) and credit card bills should be reconciled periodically.