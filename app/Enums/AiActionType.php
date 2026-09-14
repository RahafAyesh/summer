<?php

namespace App\Enums;

enum AiActionType: string
{
    case TransactionCreated = 'transaction_created';
    case TransactionUpdated = 'transaction_updated';
    case TransactionDeleted = 'transaction_deleted';
    case BudgetCreated = 'budget_created';
    case BudgetUpdated = 'budget_updated';
    case BudgetDeleted = 'budget_deleted';
    case DataExported = 'data_exported';
}
