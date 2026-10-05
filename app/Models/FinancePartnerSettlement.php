<?php

namespace App\Models;

/**
 * Money one partner handed another to settle Sri Lakshmi Micro Finance's
 * shared spending, kept separate from the other businesses' settlements.
 */
class FinancePartnerSettlement extends PartnerSettlement
{
    protected $table = 'finance_partner_settlements';
}
