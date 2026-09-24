<?php

namespace App\Models;

/**
 * Money one partner handed another to settle SN Traders' shared spending,
 * kept separate from the chit fund's settlements.
 */
class TraderPartnerSettlement extends PartnerSettlement
{
    protected $table = 'trader_partner_settlements';
}
