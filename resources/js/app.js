/*
|--------------------------------------------------------------------------
| GLOBAL APPLICATION JAVASCRIPT
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| MENU
|--------------------------------------------------------------------------
*/

window.toggleMenu = function () {

    const menu =
        document.getElementById('appMenu');

    const overlay =
        document.getElementById('menuOverlay');


    if (!menu) {
        return;
    }


    const isOpen =
        menu.classList.contains('open');


    if (isOpen) {

        menu.classList.remove('open');

        if (overlay) {
            overlay.classList.remove('open');
        }

        menu.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.classList.remove(
            'menu-open'
        );

    } else {

        menu.classList.add('open');

        if (overlay) {
            overlay.classList.add('open');
        }

        menu.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.classList.add(
            'menu-open'
        );

    }

};


/*
|--------------------------------------------------------------------------
| MENU GROUPS (e.g. Customers → All / Add / Edit)
|--------------------------------------------------------------------------
*/

window.toggleMenuGroup = function (button) {

    const group =
        button.closest('.menu-group');


    if (!group) {
        return;
    }


    const isOpen =
        group.classList.toggle('open');


    button.setAttribute(
        'aria-expanded',
        isOpen ? 'true' : 'false'
    );

};


/*
|--------------------------------------------------------------------------
| ADMIN DROPDOWN (header name → Change Password / Logout)
|--------------------------------------------------------------------------
*/

window.toggleAdminMenu = function (forceOpen) {

    const dropdown =
        document.getElementById('adminDropdown');

    const menu =
        document.getElementById('adminMenu');


    if (!dropdown || !menu) {
        return;
    }


    const shouldOpen =
        typeof forceOpen === 'boolean'
            ? forceOpen
            : menu.hidden;


    menu.hidden = !shouldOpen;

    dropdown.classList.toggle(
        'open',
        shouldOpen
    );

    dropdown
        .querySelector('.admin-toggle')
        .setAttribute(
            'aria-expanded',
            shouldOpen ? 'true' : 'false'
        );

};


document.addEventListener(
    'click',
    function (event) {

        const dropdown =
            document.getElementById('adminDropdown');

        if (
            dropdown &&
            !dropdown.contains(event.target)
        ) {
            window.toggleAdminMenu(false);
        }

    }
);


/*
|--------------------------------------------------------------------------
| THEME
|--------------------------------------------------------------------------
*/

window.toggleTheme = function () {

    const html =
        document.documentElement;

    const currentTheme =
        html.classList.contains('dark')
            ? 'dark'
            : 'light';


    const newTheme =
        currentTheme === 'dark'
            ? 'light'
            : 'dark';


    html.classList.remove(
        'dark',
        'light'
    );


    html.classList.add(
        newTheme
    );


    localStorage.setItem(
        'sn-theme',
        newTheme
    );

    /* The sun/moon icon swaps via CSS (see .theme-sun / .theme-moon in app.css). */

};


/*
|--------------------------------------------------------------------------
| TRANSLATIONS
|--------------------------------------------------------------------------
| Single, shared dictionary used across every page (header, menu,
| footer, customers list, add-customer form). An element opts in to
| translation with data-i18n="key" (for text) or
| data-i18n-placeholder="key" (for input placeholders).
*/

window.SN_TRANSLATIONS = {

    en: {

        // Header / menu / footer chrome
        brand_tagline: 'Trust · Growth · Together',
        admin: 'Admin',
        administrator: 'Administrator',
        logout: 'Logout',
        menu_main: 'MAIN',
        menu_home: 'Home',
        menu_customers: 'Customers',
        menu_groups: 'Groups',
        menu_payments: 'Payments',
        menu_draws: 'Draws',
        menu_management: 'MANAGEMENT',
        menu_reports: 'Reports',
        menu_settings: 'Settings',
        footer_secure: 'Secure',
        footer_reliable: 'Reliable',
        footer_always: 'Always With You',

        // Customers (index) page
        customers_title: 'Customers',
        customers_subtitle: 'Manage all your chit fund customers',
        add_customer: 'Add Customer',
        customer_id: 'Customer ID',
        name: 'Name',
        phone: 'Phone',
        identification: 'Identification',
        status: 'Status',
        groups: 'Groups',
        search_customers: 'Search customers...',
        sort_newest: 'Newest first',
        sort_oldest: 'Oldest first',
        sort_name_asc: 'Name A → Z',
        sort_name_desc: 'Name Z → A',
        sort_code_asc: 'Customer ID ↑',
        sort_code_desc: 'Customer ID ↓',
        sort_active_first: 'Active first',
        sort_inactive_first: 'Inactive first',
        menu_all_customers: 'All Customers',

        // Dashboard
        greeting_morning: 'Good morning',
        greeting_afternoon: 'Good afternoon',
        greeting_evening: 'Good evening',
        dashboard_subtitle: "Here's what's happening with SN Chit Funds today.",
        collected_this_month: 'Collected this month',
        pending_collections: 'Pending collections',
        members_owe: 'members to collect from',
        members_pending: 'members past their due date',
        pending_word: 'pending',
        overdue_earlier: 'overdue from earlier months',
        current_month: 'Current month',
        collection: 'collection',
        view_all: 'View all',

        // Groups
        menu_all_groups: 'All Groups',
        add_group: 'Add Group',
        edit_group: 'Edit Group',
        groups_badge: 'Chit Groups',
        groups_subtitle: 'All chit groups, their status and schedule.',
        group_form_description: 'Set up the chit amount, members and the month-by-month withdrawal schedule.',
        back_to_groups: 'Back to Groups',
        back_to_group: 'Back to Group',
        group_details: 'Group details',
        group_name: 'Group Name',
        group_name_placeholder: 'e.g. 2 Lakh – Jan 2027',
        group_type: 'Group Type',
        type_draw: 'Draw',
        type_auction: 'Auction',
        type_draw_hint: 'Winner picked by draw each month',
        type_auction_hint: 'Auction steps coming later',
        chit_amount: 'Chit Amount (₹)',
        group_months: 'Number of Months',
        group_members: 'Members',
        group_member_count: 'Number of Members',
        commission: 'Company Commission (₹ per month)',
        start_date: 'Start Date',
        monthly_installment: 'Monthly Installment',
        installment_label: 'Monthly Installment (₹)',
        withdrawal_schedule: 'Withdrawal schedule',
        copy_from_group: 'Copy from existing group…',
        copy: 'Copy',
        schedule_hint: 'Type the amount the winner takes home in each month. Rows follow the number of months above.',
        month_number: 'Month',
        month_date: 'Period',
        withdrawal_amount: 'Withdrawal Amount (₹)',
        save_group: 'Save Group',
        save_changes: 'Save Changes',
        status_all: 'All',
        status_forming: 'Forming',
        status_running: 'Running',
        status_completed: 'Completed',
        groups_empty_title: 'No groups yet',
        groups_empty_text: 'Create your first chit group to start adding members.',
        start_group: 'Start Group',
        started_on: 'Started on',
        total: 'Total',
        this_month: 'This month',

        // Group members
        edit_members: 'Edit Members',
        add_members: 'Add members',
        current_members: 'Current members',
        members_empty: 'No members yet. Use Edit Members to add customers to this group.',
        members_none_yet: 'No members yet. Search on the left and click Add.',
        member_search_placeholder: 'Search by name, ID, phone or identification',
        member_search_hint: 'Start typing to find customers. Customers already in this group are not shown.',
        member_search_none: 'No active customers match your search (members already in this group are hidden).',
        add: 'Add',
        remove: 'Remove',
        details: 'Details',
        call: 'Call',
        showing: 'Showing',
        of: 'of',

        // Payments
        collect_payment: 'Collect Payment',
        collect_payment_subtitle: 'Pick a member, choose the month and record the payment.',
        all_payments: 'All Payments',
        find_customer: 'Members to collect from',
        payment_search_placeholder: 'Search by name, ID, phone, identification or group',
        payment_search_none: 'No members with dues match your search.',
        nothing_to_collect: 'Nothing to collect — every member is paid up for this month.',
        seats_to_collect: 'to collect',
        collect_state_pending: 'Pending',
        collect_state_partial: 'Part paid',
        collect_state_due: 'Due',
        change_customer: 'Change',
        choose_seat: 'Choose the seat',
        month_word: 'month',
        due: 'due',
        paid_up: 'Paid up',
        fully_paid: 'Fully paid',
        next_month: 'Next',
        collect: 'Collect',
        record_payment: 'Record payment',
        due_now: 'Due now',
        paid_so_far: 'Paid so far',
        left_for_group: 'Left for the whole group',
        pay_full: 'Full month(s)',
        pay_partial: 'Partial / daily amount',
        how_many_months: 'How many months?',
        amount_rupees: 'Amount (₹)',
        payment_method: 'Payment method',
        method_cash: 'Cash',
        method_upi: 'UPI',
        method_bank: 'Bank transfer',
        method_cheque: 'Cheque',
        reference_number: 'Reference / UPI / cheque no.',
        payment_date_time: 'Payment date & time',
        date_time: 'Date & time',
        authorised_signature: 'Authorised signature',
        receipt_thanks: 'Thank you for your payment.',
        partly_paid_note: 'is part paid',
        continue_partial: 'Continue with a partial payment.',
        notes: 'Notes',
        save_payment: 'Save & get receipt',
        month_by_month: 'Month-by-month',
        period: 'Period',
        paid: 'Paid',
        balance: 'Balance',
        ledger_paid: 'Paid',
        ledger_partial: 'Part paid',
        ledger_due: 'Pending',
        ledger_upcoming: 'Upcoming',
        back_to_collect: 'Back to members to collect',
        print_receipt: 'Print',
        cancel_receipt: 'Cancel receipt',
        payment_receipt: 'Payment Receipt',
        receipt_no: 'Receipt No.',
        amount_received: 'Amount received',
        received_from: 'Received from',
        group_word: 'Group',
        for_months: 'For',
        part: 'part',
        balance_due_now: 'Balance due now',
        recorded_by: 'Recorded by',
        collected_today: 'Collected today',
        receipts: 'receipts',
        payment_filter_placeholder: 'Receipt no., customer or group',
        all_methods: 'All methods',
        clear: 'Clear',
        no_payments: 'No payments found',
        payment_ledger: 'Payment Ledger',
        download_pdf: 'Download PDF',
        ledger_subtitle: 'Group-wise, month-wise payments for every member.',
        print_ledger: 'Print ledger',
        ledger_no_groups: 'No started groups yet',
        ledger_no_groups_text: 'The ledger shows groups once they have started.',
        from_month: 'From month',
        to_month: 'To month',
        show: 'Show',
        months_shown: 'Months',
        collected: 'Collected',
        ledger_key_note: 'Amounts in ₹. Month totals show collected / expected.',
        member_id_here: 'Member ID in this group',
        linked_groups: 'Groups',
        reorder_hint: 'Drag a card by its handle to change the order. It saves automatically.',

        // Add-customer page
        heroBadge: 'Customer Registration',
        heroTitle: 'Add Customer',
        heroDescription: 'Create a new customer profile and keep all their chit fund information organized.',
        backText: 'Back to Customers',
        customerIdTitle: 'Customer ID',
        customerIdText: 'A unique customer ID will be generated automatically when you save the customer.',
        nameLabel: 'Name',
        phoneLabel: 'Phone Number',
        emailLabel: 'Email',
        addressLabel: 'Address',
        remarksLabel: 'Remarks / Identification',
        cancelText: 'Cancel',
        saveText: 'Save Customer',
        errorTitle: 'Please correct the following:',
        namePlaceholder: 'Enter customer name',
        phonePlaceholder: 'Enter phone number',
        emailPlaceholder: 'Enter email address',
        addressPlaceholder: 'Enter customer address',
        remarksPlaceholder: 'Optional notes or identification',
        customerAddedTitle: 'Customer Added Successfully',
        customerAddedMessage: 'The customer has been added successfully.',

        // Admin login page
        loginBadge: 'Admin Access',
        loginTitle: 'Welcome Back',
        loginDescription: 'Sign in to manage customers, groups and payments.',
        usernameLabel: 'Username',
        passwordLabel: 'Password',
        usernamePlaceholder: 'Enter your username',
        passwordPlaceholder: 'Enter your password',
        rememberMe: 'Keep me signed in',
        loginButton: 'Sign In',

        // Change password page
        settingsBadge: 'Settings',
        passwordTitle: 'Change Password',
        passwordDescription: 'Choose a new password for your admin account.',
        passwordChanged: 'Your password has been changed.',
        currentPasswordLabel: 'Current Password',
        currentPasswordPlaceholder: 'Enter your current password',
        newPasswordLabel: 'New Password',
        newPasswordPlaceholder: 'At least 8 characters',
        confirmPasswordLabel: 'Confirm New Password',
        confirmPasswordPlaceholder: 'Type the new password again',
        savePasswordText: 'Save Password',

        // Draws
        run_draw: 'Run Draw',
        run_draw_subtitle: 'Tick the interested members, then spin the wheel.',
        all_draws: 'All Draws',
        pending_payouts: 'Pending Payouts',
        draw_no_groups: 'No running groups',
        draw_no_groups_text: 'Draws are held for groups that have started.',
        prize_amount: 'Prize (withdrawal) amount',
        yet_to_win: 'Members yet to win',
        last_winner: 'Last winner',
        draw_all_done: 'Every month of this group has been drawn.',
        draw_not_yet: 'The draw for month',
        draw_opens_on: 'opens on',
        draw_not_yet_text: "Each month's draw can be run once that group month has begun.",
        interested_members: 'Interested members',
        select_all: 'Select all',
        draw_hint: 'Members who have already won are not listed. Members with dues can still take part.',
        wheel_empty: 'Tick members to fill the wheel',
        spin_wheel: 'Spin the wheel',
        draw_fair_note: 'The winner is picked at random by the system and saved before the wheel stops.',
        winner_is: 'And the winner is…',
        view_and_pay: 'View draw & pay out',
        payout_paid: 'Paid out',
        payout_pending: 'Awaiting payout',
        awaiting_payout: 'Awaiting payout',
        draw_details: 'Draw details',
        drawn_on: 'Drawn on',
        drawn_by: 'Drawn by',
        in_the_draw: 'In the draw',
        payout_ack_title: 'Prize money handed over',
        print_voucher: 'Print voucher',
        record_payout: 'Record payout',
        record_payout_hint: 'When the winner receives the prize money, record it here. This marks the draw as paid and makes the voucher for the customer to sign.',
        payout_amount: 'Amount paid (₹)',
        confirm_payout: 'Confirm paid & make voucher',
        cancel_draw: 'Cancel draw (entered by mistake)',
        payout_voucher: 'Prize Payout Voucher',
        voucher_no: 'Voucher No.',
        paid_to: 'Paid to',
        draw_word: 'Draw',
        amount_paid: 'Amount paid',
        received_by_customer: 'Received by (customer signature)',
        voucher_thanks: 'Congratulations on your winning draw.',
        winners: 'winners',
        draw_search_placeholder: 'Winner, member ID, group or voucher no.',
        no_draws: 'No draws yet',
        prize_won: 'Prize won',
        ledger_won: 'Prize money won that month',
        won_word: 'Won',
        draw_details_menu: 'Draw Details',
        current_draws: 'Current',
        past_winners: 'Past Winners',
        past_winners_hint: 'Winners whose payout voucher has been printed.',
        voucher_printed: 'Voucher printed',
        no_past_winners: 'No past winners yet',
        no_past_winners_text: 'A draw moves here once its payout voucher is printed.',
        voucher_printed_on: 'Voucher printed on',
        moved_to_past_winners: 'moved to Past Winners',
        winners_report: 'Winners Report',
        winners_report_subtitle: 'Month-wise draw winners for every group.',
        print_report: 'Print report',
        all_groups: 'All groups',
        draws_held: 'Draws held',
        total_prize: 'Total prize',
        months_drawn: 'months drawn',
        winner: 'Winner',
        prize_word: 'Prize',
        payout_word: 'Payout',
        back_to_draw_details: 'Back to Draw Details',
        back_to_past_winners: 'Back to Past Winners',
        back_to_home: 'Back to Home',
        draws_due_now: 'Draws due now',
        draws_due_note: "Groups ready for this month's draw",
        prizes_paid_this_month: 'Prizes paid this month',
        run_word: 'Run',
        no_draws_due: 'No draws due right now.',
        recent_winners: 'Recent winners',
        collect_state_clear: 'Paid up',
        collect_state_upcoming: 'Upcoming',
        traders_tagline: 'Quality Rice · Fair Price',
        menu_sales: 'Sales',
        menu_purchases: 'Purchases',
        menu_stock: 'Stock',
        menu_customer_credit: 'Customer Credit',
        menu_masters: 'MASTERS',
        new_sale: 'New Sale',
        all_sales: 'All Sales',
        new_purchase: 'New Purchase',
        all_purchases: 'All Purchases',
        receive_payment: 'Receive Payment',
        customer_balances: 'Customer Balances',
        all_receipts: 'All Receipts',
        rice_varieties: 'Rice Varieties',
        suppliers: 'Suppliers',
        traders_dashboard_subtitle: 'Here\'s what\'s happening with SN Traders today.',
        sales_today: 'Sales today',
        sales_this_month: 'Sales this month',
        purchases_this_month: 'Purchases this month',
        rice_bought_note: 'rice bought from suppliers',
        customer_credit: 'Customer credit',
        customers_owe_money: 'customers owe money',
        customer_owes_money: 'customer owes money',
        rice_in_stock: 'Rice in stock',
        who_owes_most: 'Who owes the most',
        latest_sales: 'Latest sales',
        no_sales_yet: 'No sales yet',
        add_rice_varieties: 'Add your rice varieties to start',
        nobody_owes: 'Nobody owes anything.',
        invoices_word: 'invoices',
        invoice_word: 'invoice',
        menu_usage: 'Usage',
        usage_subtitle: 'How much the app is used, day by day and month by month, and who used it recently.',
        usage_today: 'Today',
        pages_opened: 'pages opened',
        person_word: 'person',
        people_word: 'people',
        active_days: 'Days used this month',
        days_with_use: 'days the app was opened',
        average_per_day: 'Average a day',
        pages_this_month: 'pages a day this month',
        daily_usage: 'Daily usage',
        monthly_usage: 'Monthly usage',
        last_30_days: 'Last 30 days · pages opened',
        last_12_months: 'Last 12 months · pages opened',
        who_used_recently: 'Who used the app recently',
        no_customer_usage: 'No customer has used the app yet. Customers will appear here once they can sign in.',
        no_usage_yet: 'Nothing recorded yet.',
        person_title: 'Person',
        last_seen: 'Last seen',
        last_page: 'Last page',
        days_used: 'Days used',
        staff_word: 'Staff',
        recent_activity: 'Recent activity',
        time_word: 'Time',
        page_word: 'Page',
        business_word: 'Business',
        device_word: 'Device',
        device_phone: 'Phone',
        device_tablet: 'Tablet',
        device_computer: 'Computer',
        ip_address: 'IP address',
        signed_in: 'Signed in',
        kept_a_month: 'Pages opened are kept for a month',
        rice_sales_report: 'Rice Sales',
        rice_sales_subtitle: 'Which rice sells fastest — bags sold, share of sales, and how long the stock lasts at this pace.',
        profit_loss: 'Profit & Loss',
        profit_subtitle: 'Sales less the purchase cost of the rice sold, less expenses — the profit shared by the partners.',
        customer_dues: 'Customer Dues',
        customer_dues_subtitle: 'Who has to pay and how long it has been unpaid. Money received pays off the oldest invoices first.',
        day_book: 'Day Book',
        day_book_subtitle: 'Each day: sales, money received, rice purchased and expenses, and the money left at the end of the day.',
        day_book_note: 'Money in is what customers paid that day (at a sale or later). Money out is rice purchased plus expenses.',
        traders_statement_subtitle: 'Find a customer to see every invoice and payment with the running balance — print or download it.',
        speed_fast: 'Fast',
        speed_steady: 'Steady',
        speed_slow: 'Slow',
        speed_none: 'Not sold',
        speed_word: 'Speed',
        age_0_30: '0–30 days',
        age_31_60: '31–60 days',
        age_61_90: '61–90 days',
        age_over_90: 'Over 90 days',
        everyone_owing: 'Everyone who owes',
        older_30: 'Unpaid over 30 days',
        older_60: 'Unpaid over 60 days',
        older_90: 'Unpaid over 90 days',
        show_word: 'Show',
        oldest_unpaid: 'Oldest unpaid',
        last_paid: 'Last paid',
        days_word: 'days',
        avg_rate_bag: 'Avg rate / bag',
        bags_a_day: 'bags a day',
        bags_per_day: 'Bags / day',
        bags_sold: 'Bags sold',
        stock_lasts: 'Stock lasts',
        fastest_selling: 'Fastest selling rice',
        top_customers: 'Top customers',
        invoices_title: 'Invoices',
        cost_word: 'Cost',
        profit_word: 'Profit',
        margin_word: 'Margin',
        gross_profit: 'Gross profit',
        net_profit: 'Net profit',
        less_cost_of_rice: 'Less: cost of the rice sold',
        less_expenses: 'Less: expenses',
        entries_word: 'entries',
        share_of: 'Share of',
        profit_statement: 'Profit statement',
        profit_by_rice: 'Profit by rice',
        money_view: 'For reference',
        rice_purchased_period: 'Rice purchased in this period',
        purchases_note: 'Not all of it is sold yet, so it is not the cost above.',
        stock_value: 'Rice in stock now, at cost',
        missing_cost_note: 'No purchase price for',
        set_prices: 'set the prices in Rice Varieties',
        no_purchase_price_for: 'No purchase price was set for',
        profit_on_sale: 'Profit on this sale',
        purchase_price: 'Purchase price',
        selling_price: 'Selling price',
        purchase_price_bag: 'Purchase price / bag (₹)',
        selling_price_bag: 'Selling price / bag (₹)',
        profit_per_bag: 'Profit / bag',
        money_in: 'Money in',
        net_cash: 'Money in − money out',
        net_word: 'Net',
        purchases_word_title: 'Purchases',
        nothing_in_period: 'Nothing recorded in this period',
        no_trading_customers: 'No customer found who has bought rice.',
        recent_customers: 'Customers who bought most recently',
        date: 'Date',
        rate_per_bag: 'Rate / bag',
        rate_per_bag_rupees: 'Rate per bag (₹)',
        received_word: 'received',
        new_purchase_subtitle: 'Rice bought from a supplier. Stock goes up by what you enter.',
        new_sale_subtitle: 'Rice sold to a customer. Anything not paid now goes on their credit.',
        purchase_needs_masters: 'Add at least one rice variety and one supplier first.',
        sale_needs_varieties: 'Add your rice varieties first.',
        supplier_word: 'Supplier',
        add_new_supplier: '+ Add a new supplier',
        supplier_bill_no: 'Supplier\'s bill no.',
        rice_bought: 'Rice bought',
        rice_sold: 'Rice sold',
        save_purchase: 'Save purchase',
        customer_word: 'Customer',
        type_to_find_customer: 'Type to find the customer',
        already_owes: 'Already owes',
        add_new_customer: '+ Add a new customer',
        received_now: 'Received now',
        full_word: 'Full',
        save_invoice: 'Save & get invoice',
        rice_variety: 'Rice variety',
        bags_word: 'Bags',
        bags_lower: 'bags',
        bag_kg: 'Kg / bag',
        rate_word: 'Rate (₹)',
        amount_word: 'Amount',
        add_line: 'Add another rice',
        total_word: 'Total',
        bill_total: 'Bill total',
        customer_account: 'Customer account',
        delete_invoice: 'Delete invoice',
        delete_purchase: 'Delete purchase',
        sales_subtitle: 'Rice sold to customers. This month by default.',
        purchases_subtitle: 'Rice bought from suppliers. This month by default.',
        received_at_sale: 'Received at sale',
        on_credit: 'on credit',
        paid_word: 'Paid',
        no_sales: 'No sales in this period',
        no_purchases: 'No purchases in this period',
        purchases_word: 'purchases',
        stock_subtitle: 'Rice on hand — everything bought minus everything sold — as on',
        varieties_word: 'varieties',
        no_varieties: 'No rice varieties yet',
        bought_bags: 'Bought (bags)',
        sold_bags: 'Sold (bags)',
        in_stock_bags: 'In stock (bags)',
        kg_bag: 'kg bag',
        balances_subtitle: 'What each customer owes SN Traders — sales minus money received.',
        customers_owing: 'Owe money',
        advances_word: 'Advances',
        customers_owe_total: 'Customers owe',
        customers_word: 'customers',
        customer_lower: 'customer',
        advances_held: 'Advances held',
        last_sale: 'Last sale',
        sales_word: 'Sales',
        advance_word: 'advance',
        total_sales: 'Total sales',
        total_received: 'Total received',
        balance_due: 'Balance due',
        entry_word: 'Entry',
        sale_word: 'Sale',
        no_trades_yet: 'No sales or receipts yet.',
        receive_payment_subtitle: 'Money a customer pays against their rice credit.',
        owes_word: 'Owes',
        nothing_owed: 'Nothing owed',
        for_invoice: 'For invoice',
        receipts_subtitle: 'Money received from customers — at the sale and later. This month by default.',
        no_receipts: 'No receipts in this period',
        varieties_subtitle: 'The rice you buy and sell, with the bag size and the purchase and selling price per bag. A new purchase bill updates the purchase price.',
        add_variety: 'Add a variety',
        variety_name: 'Name',
        add_word: 'Add',
        edit: 'Edit',
        inactive_word: 'inactive',
        active_word: 'Active',
        suppliers_subtitle: 'Mills and wholesalers you buy rice from.',
        add_supplier: 'Add a supplier',
        supplier_name: 'Name',
        place_word: 'Place',
        no_suppliers: 'No suppliers yet',
        bought_word: 'Bought',
        menu_expenses: 'Expenses',
        add_expense: 'Add Expense',
        edit_expense: 'Edit Expense',
        all_expenses: 'All Expenses',
        balance_sheet: 'Balance Sheet',
        back_to_expenses: 'Back to Expenses',
        expense_form_subtitle: 'Business spending paid by a partner. It is shared equally between the partners.',
        paid_by_partner: 'Paid by',
        expense_date: 'Date',
        spent_for: 'What was it for?',
        paid_to_whom: 'Paid to (shop / person)',
        bill_number: 'Bill / reference no.',
        bill_short: 'Bill',
        save_and_add_another: 'Save & add another',
        save_expense: 'Save expense',
        delete_expense: 'Delete this expense',
        expenses_subtitle: 'Business spending by the partners. This month by default.',
        both_partners: 'All partners',
        expense_search_placeholder: 'What for, paid to, bill no.',
        expenses_word: 'expenses',
        no_expenses: 'No expenses in this period',
        range_year: 'This year',
        range_all: 'All time',
        balance_sheet_subtitle: 'Business spending shared equally between the partners, and who pays whom to square up.',
        balance_period_note: 'These balances cover this period only. Choose "All time" for the running balance between the partners.',
        total_spent: 'Total spent',
        each_share: 'Each partner\'s share',
        partners_word: 'partners',
        partners_heading: 'Partners',
        partner_word: 'Partner',
        spent_paid: 'Paid for expenses',
        equal_share: 'Equal share',
        settlement_given: 'Settlements given',
        settlement_received: 'Settlements received',
        net_put_in: 'Net put in',
        to_receive: 'to receive',
        to_pay: 'to pay',
        settled_word: 'Settled',
        who_pays_whom: 'Who pays whom',
        pays_word: 'pays',
        record_this_payment: 'Record this payment',
        all_square: 'All square — nobody owes anybody.',
        record_settlement: 'Record a settlement',
        settlement_from: 'Paid by',
        settlement_to: 'Paid to',
        save_settlement: 'Save settlement',
        settlements_heading: 'Settlements',
        no_settlements: 'No settlements in this period.',
        pending_and_due: 'Pending & Due',
        dues_report_subtitle: 'Who still has to pay, as on',
        pending_list_title: 'Past the due date',
        due_list_title: 'Due this month',
        nobody_on_list: 'Nobody on this list.',
        due_date: 'Due date',
        days_overdue: 'Days overdue',
        by_group: 'By group',
        all_payments_subtitle: "Today's payments. Use the dates and filters to see earlier ones.",
        range_today: 'Today',
        range_yesterday: 'Yesterday',
        range_week: 'This week',
        range_month: 'This month',
        range_last_month: 'Last month',
        from_date: 'From',
        to_date: 'To',
        back_to_today: 'Back to today',
        receipt_word: 'Receipt',
        print_list: 'Print list',
        no_payments_today: 'No payments today yet',
        no_payments_today_text: 'Choose another date or range above to see earlier payments.',
        prizes_won: 'Prizes won',
        open_full_form: 'Open full form',
        all_months_paid_short: 'All months paid',
        nothing_paid_yet: 'Nothing paid yet',
        paid_up_to: 'Paid up to',
        part_paid_lower: 'part paid',
        due_collections: 'Due collections',
        members_due: 'members due this month',
        next_member: 'Next member',
        more_details: 'More details',
        more_details_hint: 'date & time, notes',
        upcoming_word: 'upcoming',
        nothing_due: 'No members are due right now. Search to find anyone else.',
        seats_due: 'due — search to find anyone else',
        all_months_paid: 'Every month of this group is paid.',
        choose_month: 'Which month is this for?',
        due_on: 'due',
        month_one_full_only: 'Month 1 is paid in full — no partial payments.',
        continue_partial_month: 'This month is part paid. Continue with a partial payment.',
        pay_full_month: 'Full month',
        nothing_pending: 'No pending members right now. Search to find anyone else.',
        payment_search_none_any: 'No members match your search.',
        seats_pending: 'pending — search to find anyone else',
        seats_found: 'found',
        months_word: 'Months',
        customer_statement: 'Customer Statement',
        customer_statement_subtitle: 'Every payment a customer made, month by month, for each group.',
        find_another_customer: 'Find another customer',
        print_statement: 'Print statement',
        statement_search_placeholder: 'Type a customer name, ID, phone or identification',
        statement_search_hint: 'Start typing a name to find the customer.',
        statement_search_none: 'No customers in any group match your search.',
        seats_word: 'seat(s)',
        address: 'Address',
        total_paid: 'Total paid',
        statement_no_groups: 'This customer is not in any started group yet.',
        no_payments_yet: 'No payments yet'

    },

    ta: {

        // Header / menu / footer chrome
        brand_tagline: 'நம்பிக்கை · வளர்ச்சி · ஒன்றாக',
        admin: 'நிர்வாகி',
        administrator: 'நிர்வாகி',
        logout: 'வெளியேறு',
        menu_main: 'முதன்மை',
        menu_home: 'முகப்பு',
        menu_customers: 'வாடிக்கையாளர்கள்',
        menu_groups: 'குழுக்கள்',
        menu_payments: 'கட்டணங்கள்',
        menu_draws: 'சீட்டு',
        menu_management: 'மேலாண்மை',
        menu_reports: 'அறிக்கைகள்',
        menu_settings: 'அமைப்புகள்',
        footer_secure: 'பாதுகாப்பானது',
        footer_reliable: 'நம்பகமானது',
        footer_always: 'எப்போதும் உங்களுடன்',

        // Customers (index) page
        customers_title: 'வாடிக்கையாளர்கள்',
        customers_subtitle: 'உங்கள் சீட்டு நிதி வாடிக்கையாளர்களை நிர்வகிக்கவும்',
        add_customer: 'வாடிக்கையாளரைச் சேர்க்கவும்',
        customer_id: 'வாடிக்கையாளர் அடையாள எண்',
        name: 'பெயர்',
        phone: 'தொலைபேசி',
        identification: 'அடையாளம்',
        status: 'நிலை',
        groups: 'குழுக்கள்',
        search_customers: 'வாடிக்கையாளர்களைத் தேடுங்கள்...',
        sort_newest: 'புதியவை முதலில்',
        sort_oldest: 'பழையவை முதலில்',
        sort_name_asc: 'பெயர் A → Z',
        sort_name_desc: 'பெயர் Z → A',
        sort_code_asc: 'வாடிக்கையாளர் எண் ↑',
        sort_code_desc: 'வாடிக்கையாளர் எண் ↓',
        sort_active_first: 'செயலில் உள்ளவை முதலில்',
        sort_inactive_first: 'செயலற்றவை முதலில்',
        menu_all_customers: 'அனைத்து வாடிக்கையாளர்கள்',

        // Dashboard
        greeting_morning: 'காலை வணக்கம்',
        greeting_afternoon: 'மதிய வணக்கம்',
        greeting_evening: 'மாலை வணக்கம்',
        dashboard_subtitle: 'இன்று SN சிட் ஃபண்ட்ஸில் நடப்பவை.',
        collected_this_month: 'இந்த மாத வசூல்',
        pending_collections: 'நிலுவை வசூல்',
        members_owe: 'உறுப்பினர்களிடம் வசூலிக்க வேண்டும்',
        members_pending: 'உறுப்பினர்கள் கெடு தாண்டியவர்கள்',
        pending_word: 'நிலுவை',
        overdue_earlier: 'முந்தைய மாதங்களின் நிலுவை',
        current_month: 'நடப்பு மாதம்',
        collection: 'வசூல்',
        view_all: 'அனைத்தையும் காண்க',

        // Groups
        menu_all_groups: 'அனைத்து குழுக்கள்',
        add_group: 'குழுவைச் சேர்க்கவும்',
        edit_group: 'குழுவைத் திருத்து',
        groups_badge: 'சீட்டு குழுக்கள்',
        groups_subtitle: 'அனைத்து சீட்டு குழுக்கள், அவற்றின் நிலை மற்றும் அட்டவணை.',
        group_form_description: 'சீட்டு தொகை, உறுப்பினர்கள் மற்றும் மாதாந்திர எடுப்பு அட்டவணையை அமைக்கவும்.',
        back_to_groups: 'குழுக்களுக்குத் திரும்பு',
        back_to_group: 'குழுவுக்குத் திரும்பு',
        group_details: 'குழு விவரங்கள்',
        group_name: 'குழு பெயர்',
        group_name_placeholder: 'எ.கா. 2 லட்சம் – ஜன 2027',
        group_type: 'குழு வகை',
        type_draw: 'குலுக்கல்',
        type_auction: 'ஏலம்',
        type_draw_hint: 'ஒவ்வொரு மாதமும் குலுக்கல் மூலம் வெற்றியாளர்',
        type_auction_hint: 'ஏல நடைமுறை பின்னர் வரும்',
        chit_amount: 'சீட்டு தொகை (₹)',
        group_months: 'மாதங்களின் எண்ணிக்கை',
        group_members: 'உறுப்பினர்கள்',
        group_member_count: 'உறுப்பினர்களின் எண்ணிக்கை',
        commission: 'நிறுவன கமிஷன் (மாதத்திற்கு ₹)',
        start_date: 'தொடக்க தேதி',
        monthly_installment: 'மாதாந்திர தவணை',
        installment_label: 'மாதாந்திர தவணை (₹)',
        withdrawal_schedule: 'எடுப்பு அட்டவணை',
        copy_from_group: 'ஏற்கனவே உள்ள குழுவிலிருந்து நகலெடு…',
        copy: 'நகலெடு',
        schedule_hint: 'ஒவ்வொரு மாதமும் வெற்றியாளர் பெறும் தொகையை உள்ளிடவும். வரிசைகள் மேலே உள்ள மாதங்களின் எண்ணிக்கையைப் பின்பற்றும்.',
        month_number: 'மாதம்',
        month_date: 'காலம்',
        withdrawal_amount: 'எடுப்பு தொகை (₹)',
        save_group: 'குழுவைச் சேமி',
        save_changes: 'மாற்றங்களைச் சேமி',
        status_all: 'அனைத்தும்',
        status_forming: 'உருவாக்கத்தில்',
        status_running: 'நடப்பில்',
        status_completed: 'முடிந்தது',
        groups_empty_title: 'இன்னும் குழுக்கள் இல்லை',
        groups_empty_text: 'உறுப்பினர்களைச் சேர்க்க முதல் சீட்டு குழுவை உருவாக்கவும்.',
        start_group: 'குழுவைத் தொடங்கு',
        started_on: 'தொடங்கிய தேதி',
        total: 'மொத்தம்',
        this_month: 'இந்த மாதம்',

        // Group members
        edit_members: 'உறுப்பினர்களைத் திருத்து',
        add_members: 'உறுப்பினர்களைச் சேர்',
        current_members: 'தற்போதைய உறுப்பினர்கள்',
        members_empty: 'இன்னும் உறுப்பினர்கள் இல்லை. வாடிக்கையாளர்களைச் சேர்க்க "உறுப்பினர்களைத் திருத்து" பயன்படுத்தவும்.',
        members_none_yet: 'இன்னும் உறுப்பினர்கள் இல்லை. இடதுபுறம் தேடி "சேர்" கிளிக் செய்யவும்.',
        member_search_placeholder: 'பெயர், எண், தொலைபேசி அல்லது அடையாளம் மூலம் தேடவும்',
        member_search_hint: 'வாடிக்கையாளர்களைக் கண்டறிய தட்டச்சு செய்யவும். ஏற்கனவே இந்தக் குழுவில் உள்ளவர்கள் காட்டப்படமாட்டார்கள்.',
        member_search_none: 'உங்கள் தேடலுக்குப் பொருந்தும் செயலில் உள்ள வாடிக்கையாளர்கள் இல்லை (ஏற்கனவே இந்தக் குழுவில் உள்ளவர்கள் மறைக்கப்பட்டுள்ளனர்).',
        add: 'சேர்',
        remove: 'நீக்கு',
        details: 'விவரங்கள்',
        call: 'அழை',
        showing: 'காட்டப்படுவது',
        of: 'மொத்தம்',

        // Payments
        collect_payment: 'பணம் வசூல்',
        collect_payment_subtitle: 'உறுப்பினரைத் தேர்ந்தெடுத்து, மாதத்தைத் தேர்வு செய்து கட்டணத்தைப் பதிவு செய்யவும்.',
        all_payments: 'அனைத்து கட்டணங்கள்',
        find_customer: 'வசூலிக்க வேண்டிய உறுப்பினர்கள்',
        payment_search_placeholder: 'பெயர், எண், தொலைபேசி, அடையாளம் அல்லது குழு மூலம் தேடவும்',
        payment_search_none: 'உங்கள் தேடலுக்குப் பொருந்தும் நிலுவை உள்ள உறுப்பினர்கள் இல்லை.',
        nothing_to_collect: 'வசூலிக்க எதுவும் இல்லை — அனைவரும் இந்த மாதம் செலுத்திவிட்டனர்.',
        seats_to_collect: 'வசூலிக்க',
        collect_state_pending: 'நிலுவை',
        collect_state_partial: 'பகுதி',
        collect_state_due: 'செலுத்த வேண்டும்',
        change_customer: 'மாற்று',
        choose_seat: 'இடத்தைத் தேர்ந்தெடு',
        month_word: 'மாதம்',
        due: 'நிலுவை',
        paid_up: 'செலுத்தப்பட்டது',
        fully_paid: 'முழுவதும் செலுத்தப்பட்டது',
        next_month: 'அடுத்து',
        collect: 'வசூல்',
        record_payment: 'கட்டணத்தைப் பதிவு செய்',
        due_now: 'தற்போதைய நிலுவை',
        paid_so_far: 'இதுவரை செலுத்தியது',
        left_for_group: 'குழு முழுவதும் மீதம்',
        pay_full: 'முழு மாதம்',
        pay_partial: 'பகுதி / தினசரி தொகை',
        how_many_months: 'எத்தனை மாதங்கள்?',
        amount_rupees: 'தொகை (₹)',
        payment_method: 'செலுத்தும் முறை',
        method_cash: 'ரொக்கம்',
        method_upi: 'UPI',
        method_bank: 'வங்கி பரிமாற்றம்',
        method_cheque: 'காசோலை',
        reference_number: 'குறிப்பு / UPI / காசோலை எண்',
        payment_date_time: 'செலுத்திய தேதி & நேரம்',
        date_time: 'தேதி & நேரம்',
        authorised_signature: 'அங்கீகரிக்கப்பட்ட கையொப்பம்',
        receipt_thanks: 'உங்கள் கட்டணத்திற்கு நன்றி.',
        partly_paid_note: 'பகுதியாக செலுத்தப்பட்டுள்ளது',
        continue_partial: 'பகுதி கட்டணத்தைத் தொடரவும்.',
        notes: 'குறிப்புகள்',
        save_payment: 'சேமித்து ரசீது பெறு',
        month_by_month: 'மாதவாரியாக',
        period: 'காலம்',
        paid: 'செலுத்தியது',
        balance: 'மீதம்',
        ledger_paid: 'செலுத்தப்பட்டது',
        ledger_partial: 'பகுதி',
        ledger_due: 'நிலுவை',
        ledger_upcoming: 'வரவிருக்கும்',
        back_to_collect: 'வசூலிக்க வேண்டிய உறுப்பினர்களுக்குத் திரும்பு',
        print_receipt: 'அச்சிடு',
        cancel_receipt: 'ரசீதை ரத்து செய்',
        payment_receipt: 'கட்டண ரசீது',
        receipt_no: 'ரசீது எண்',
        amount_received: 'பெறப்பட்ட தொகை',
        received_from: 'பெறப்பட்டது',
        group_word: 'குழு',
        for_months: 'எதற்காக',
        part: 'பகுதி',
        balance_due_now: 'தற்போதைய நிலுவை',
        recorded_by: 'பதிவு செய்தவர்',
        collected_today: 'இன்றைய வசூல்',
        receipts: 'ரசீதுகள்',
        payment_filter_placeholder: 'ரசீது எண், வாடிக்கையாளர் அல்லது குழு',
        all_methods: 'அனைத்து முறைகள்',
        clear: 'அழி',
        no_payments: 'கட்டணங்கள் எதுவும் இல்லை',
        payment_ledger: 'கட்டணப் பேரேடு',
        download_pdf: 'PDF பதிவிறக்கு',
        ledger_subtitle: 'ஒவ்வொரு உறுப்பினருக்கும் குழுவாரியான, மாதவாரியான கட்டணங்கள்.',
        print_ledger: 'பேரேட்டை அச்சிடு',
        ledger_no_groups: 'இன்னும் தொடங்கிய குழுக்கள் இல்லை',
        ledger_no_groups_text: 'குழு தொடங்கியதும் பேரேட்டில் காட்டப்படும்.',
        from_month: 'தொடக்க மாதம்',
        to_month: 'இறுதி மாதம்',
        show: 'காட்டு',
        months_shown: 'மாதங்கள்',
        collected: 'வசூலானது',
        ledger_key_note: 'தொகைகள் ₹-இல். மாத மொத்தம்: வசூல் / எதிர்பார்ப்பு.',
        member_id_here: 'இந்தக் குழுவில் உறுப்பினர் எண்',
        linked_groups: 'குழுக்கள்',
        reorder_hint: 'வரிசையை மாற்ற அட்டையை அதன் பிடியால் இழுக்கவும். தானாகச் சேமிக்கப்படும்.',

        // Add-customer page
        heroBadge: 'வாடிக்கையாளர் பதிவு',
        heroTitle: 'வாடிக்கையாளரைச் சேர்க்கவும்',
        heroDescription: 'புதிய வாடிக்கையாளர் சுயவிவரத்தை உருவாக்கி, அவர்களின் சீட்டு நிதி தகவல்களை ஒழுங்காக பராமரிக்கவும்.',
        backText: 'வாடிக்கையாளர்களுக்குத் திரும்பு',
        customerIdTitle: 'வாடிக்கையாளர் அடையாள எண்',
        customerIdText: 'வாடிக்கையாளரை சேமிக்கும் போது தனிப்பட்ட அடையாள எண் தானாக உருவாக்கப்படும்.',
        nameLabel: 'பெயர்',
        phoneLabel: 'தொலைபேசி எண்',
        emailLabel: 'மின்னஞ்சல்',
        addressLabel: 'முகவரி',
        remarksLabel: 'குறிப்புகள் / அடையாளம்',
        cancelText: 'ரத்து செய்',
        saveText: 'வாடிக்கையாளரை சேமிக்கவும்',
        errorTitle: 'பின்வரும் தகவல்களை சரிபார்க்கவும்:',
        namePlaceholder: 'வாடிக்கையாளர் பெயரை உள்ளிடவும்',
        phonePlaceholder: 'தொலைபேசி எண்ணை உள்ளிடவும்',
        emailPlaceholder: 'மின்னஞ்சல் முகவரியை உள்ளிடவும்',
        addressPlaceholder: 'வாடிக்கையாளர் முகவரியை உள்ளிடவும்',
        remarksPlaceholder: 'விருப்பமான குறிப்புகள் அல்லது அடையாளத்தை உள்ளிடவும்',
        customerAddedTitle: 'வாடிக்கையாளர் வெற்றிகரமாக சேர்க்கப்பட்டார்',
        customerAddedMessage: 'வாடிக்கையாளர் விவரங்கள் வெற்றிகரமாக சேமிக்கப்பட்டுள்ளன.',

        // Admin login page
        loginBadge: 'நிர்வாகி அணுகல்',
        loginTitle: 'மீண்டும் வருக',
        loginDescription: 'வாடிக்கையாளர்கள், குழுக்கள் மற்றும் கட்டணங்களை நிர்வகிக்க உள்நுழையவும்.',
        usernameLabel: 'பயனர் பெயர்',
        passwordLabel: 'கடவுச்சொல்',
        usernamePlaceholder: 'உங்கள் பயனர் பெயரை உள்ளிடவும்',
        passwordPlaceholder: 'உங்கள் கடவுச்சொல்லை உள்ளிடவும்',
        rememberMe: 'என்னை உள்நுழைந்த நிலையில் வைத்திரு',
        loginButton: 'உள்நுழை',

        // Change password page
        settingsBadge: 'அமைப்புகள்',
        passwordTitle: 'கடவுச்சொல்லை மாற்று',
        passwordDescription: 'உங்கள் நிர்வாகி கணக்கிற்கு புதிய கடவுச்சொல்லைத் தேர்ந்தெடுக்கவும்.',
        passwordChanged: 'உங்கள் கடவுச்சொல் மாற்றப்பட்டது.',
        currentPasswordLabel: 'தற்போதைய கடவுச்சொல்',
        currentPasswordPlaceholder: 'உங்கள் தற்போதைய கடவுச்சொல்லை உள்ளிடவும்',
        newPasswordLabel: 'புதிய கடவுச்சொல்',
        newPasswordPlaceholder: 'குறைந்தது 8 எழுத்துகள்',
        confirmPasswordLabel: 'புதிய கடவுச்சொல்லை உறுதிப்படுத்து',
        confirmPasswordPlaceholder: 'புதிய கடவுச்சொல்லை மீண்டும் உள்ளிடவும்',
        savePasswordText: 'கடவுச்சொல்லை சேமி',

        // Draws
        run_draw: 'குலுக்கல் நடத்து',
        run_draw_subtitle: 'விருப்பமுள்ள உறுப்பினர்களைத் தேர்ந்தெடுத்து சக்கரத்தைச் சுழற்றவும்.',
        all_draws: 'அனைத்து குலுக்கல்கள்',
        pending_payouts: 'நிலுவையில் உள்ள பரிசுத் தொகைகள்',
        draw_no_groups: 'இயங்கும் குழுக்கள் இல்லை',
        draw_no_groups_text: 'தொடங்கிய குழுக்களுக்கு மட்டுமே குலுக்கல் நடைபெறும்.',
        prize_amount: 'பரிசு (எடுப்பு) தொகை',
        yet_to_win: 'இன்னும் வெல்லாத உறுப்பினர்கள்',
        last_winner: 'கடைசி வெற்றியாளர்',
        draw_all_done: 'இந்தக் குழுவின் அனைத்து மாதங்களுக்கும் குலுக்கல் முடிந்தது.',
        draw_not_yet: 'மாதத்திற்கான குலுக்கல்',
        draw_opens_on: 'தொடங்கும் நாள்',
        draw_not_yet_text: 'குழு மாதம் தொடங்கிய பிறகே அந்த மாதக் குலுக்கலை நடத்த முடியும்.',
        interested_members: 'விருப்பமுள்ள உறுப்பினர்கள்',
        select_all: 'அனைத்தையும் தேர்ந்தெடு',
        draw_hint: 'ஏற்கனவே வென்றவர்கள் பட்டியலில் இல்லை. நிலுவை உள்ளவர்களும் பங்கேற்கலாம்.',
        wheel_empty: 'சக்கரத்தை நிரப்ப உறுப்பினர்களைத் தேர்ந்தெடுக்கவும்',
        spin_wheel: 'சக்கரத்தைச் சுழற்று',
        draw_fair_note: 'வெற்றியாளர் கணினியால் சீரற்ற முறையில் தேர்ந்தெடுக்கப்பட்டு, சக்கரம் நிற்கும் முன்பே சேமிக்கப்படுகிறார்.',
        winner_is: 'வெற்றியாளர்…',
        view_and_pay: 'குலுக்கலைப் பார்த்து பணம் வழங்கு',
        payout_paid: 'வழங்கப்பட்டது',
        payout_pending: 'வழங்க வேண்டியது',
        awaiting_payout: 'வழங்க வேண்டியது',
        draw_details: 'குலுக்கல் விவரங்கள்',
        drawn_on: 'குலுக்கல் நாள்',
        drawn_by: 'நடத்தியவர்',
        in_the_draw: 'குலுக்கலில் இருந்தவர்கள்',
        payout_ack_title: 'பரிசுத் தொகை ஒப்படைக்கப்பட்டது',
        print_voucher: 'வவுச்சர் அச்சிடு',
        record_payout: 'பணம் வழங்கியதைப் பதிவு செய்',
        record_payout_hint: 'வெற்றியாளர் பரிசுத் தொகையைப் பெற்றதும் இங்கே பதிவு செய்யவும். இது குலுக்கலை வழங்கப்பட்டதாகக் குறித்து, வாடிக்கையாளர் கையொப்பமிட வவுச்சரை உருவாக்கும்.',
        payout_amount: 'வழங்கிய தொகை (₹)',
        confirm_payout: 'வழங்கியதை உறுதிசெய்து வவுச்சர் உருவாக்கு',
        cancel_draw: 'குலுக்கலை ரத்து செய் (தவறாகப் பதிவானது)',
        payout_voucher: 'பரிசுத் தொகை வவுச்சர்',
        voucher_no: 'வவுச்சர் எண்',
        paid_to: 'பெறுநர்',
        draw_word: 'குலுக்கல்',
        amount_paid: 'வழங்கிய தொகை',
        received_by_customer: 'பெற்றவர் (வாடிக்கையாளர் கையொப்பம்)',
        voucher_thanks: 'உங்கள் குலுக்கல் வெற்றிக்கு வாழ்த்துகள்.',
        winners: 'வெற்றியாளர்கள்',
        draw_search_placeholder: 'வெற்றியாளர், உறுப்பினர் எண், குழு அல்லது வவுச்சர் எண்',
        no_draws: 'இன்னும் குலுக்கல்கள் இல்லை',
        prize_won: 'வென்ற பரிசு',
        ledger_won: 'அந்த மாதம் வென்ற பரிசுத் தொகை',
        won_word: 'வென்றார்',
        draw_details_menu: 'குலுக்கல் விவரங்கள்',
        current_draws: 'நடப்பு',
        past_winners: 'முந்தைய வெற்றியாளர்கள்',
        past_winners_hint: 'பரிசுத் தொகை வவுச்சர் அச்சிடப்பட்ட வெற்றியாளர்கள்.',
        voucher_printed: 'வவுச்சர் அச்சிடப்பட்டது',
        no_past_winners: 'இன்னும் முந்தைய வெற்றியாளர்கள் இல்லை',
        no_past_winners_text: 'வவுச்சர் அச்சிடப்பட்டதும் குலுக்கல் இங்கே வரும்.',
        voucher_printed_on: 'வவுச்சர் அச்சிடப்பட்ட நாள்',
        moved_to_past_winners: 'முந்தைய வெற்றியாளர்களுக்கு மாற்றப்பட்டது',
        winners_report: 'வெற்றியாளர் அறிக்கை',
        winners_report_subtitle: 'ஒவ்வொரு குழுவின் மாதவாரி குலுக்கல் வெற்றியாளர்கள்.',
        print_report: 'அறிக்கையை அச்சிடு',
        all_groups: 'அனைத்து குழுக்கள்',
        draws_held: 'நடந்த குலுக்கல்கள்',
        total_prize: 'மொத்த பரிசு',
        months_drawn: 'மாதங்கள் குலுக்கப்பட்டன',
        winner: 'வெற்றியாளர்',
        prize_word: 'பரிசு',
        payout_word: 'வழங்கல்',
        back_to_draw_details: 'குலுக்கல் விவரங்களுக்குத் திரும்பு',
        back_to_past_winners: 'முந்தைய வெற்றியாளர்களுக்குத் திரும்பு',
        back_to_home: 'முகப்புக்குத் திரும்பு',
        draws_due_now: 'நடத்த வேண்டிய குலுக்கல்கள்',
        draws_due_note: 'இந்த மாதக் குலுக்கலுக்குத் தயாரான குழுக்கள்',
        prizes_paid_this_month: 'இந்த மாதம் வழங்கிய பரிசுகள்',
        run_word: 'நடத்து',
        no_draws_due: 'இப்போது நடத்த வேண்டிய குலுக்கல் இல்லை.',
        recent_winners: 'சமீபத்திய வெற்றியாளர்கள்',
        collect_state_clear: 'முழுதும் செலுத்தியது',
        collect_state_upcoming: 'வரவிருக்கிறது',
        traders_tagline: 'தரமான அரிசி · நியாய விலை',
        menu_sales: 'விற்பனை',
        menu_purchases: 'கொள்முதல்',
        menu_stock: 'இருப்பு',
        menu_customer_credit: 'வாடிக்கையாளர் கடன்',
        menu_masters: 'அடிப்படை விவரங்கள்',
        new_sale: 'புதிய விற்பனை',
        all_sales: 'அனைத்து விற்பனைகள்',
        new_purchase: 'புதிய கொள்முதல்',
        all_purchases: 'அனைத்து கொள்முதல்கள்',
        receive_payment: 'பணம் பெறு',
        customer_balances: 'வாடிக்கையாளர் நிலுவைகள்',
        all_receipts: 'அனைத்து ரசீதுகள்',
        rice_varieties: 'அரிசி வகைகள்',
        suppliers: 'விற்பனையாளர்கள்',
        traders_dashboard_subtitle: 'இன்று SN Traders-இல் நடப்பவை.',
        sales_today: 'இன்றைய விற்பனை',
        sales_this_month: 'இந்த மாத விற்பனை',
        purchases_this_month: 'இந்த மாத கொள்முதல்',
        rice_bought_note: 'விற்பனையாளர்களிடம் வாங்கிய அரிசி',
        customer_credit: 'வாடிக்கையாளர் கடன்',
        customers_owe_money: 'வாடிக்கையாளர்கள் பணம் தர வேண்டும்',
        customer_owes_money: 'வாடிக்கையாளர் பணம் தர வேண்டும்',
        rice_in_stock: 'இருப்பில் உள்ள அரிசி',
        who_owes_most: 'அதிகம் தர வேண்டியவர்கள்',
        latest_sales: 'சமீபத்திய விற்பனைகள்',
        no_sales_yet: 'இன்னும் விற்பனை இல்லை',
        add_rice_varieties: 'தொடங்க அரிசி வகைகளைச் சேர்க்கவும்',
        nobody_owes: 'யாரும் எதுவும் தர வேண்டியதில்லை.',
        invoices_word: 'பில்கள்',
        invoice_word: 'பில்',
        menu_usage: 'பயன்பாடு',
        usage_subtitle: 'செயலி எவ்வளவு பயன்படுத்தப்படுகிறது — நாள் வாரியாக, மாதம் வாரியாக — சமீபத்தில் யார் பயன்படுத்தினார்கள்.',
        usage_today: 'இன்று',
        pages_opened: 'பக்கங்கள் திறக்கப்பட்டன',
        person_word: 'நபர்',
        people_word: 'நபர்கள்',
        active_days: 'இந்த மாதம் பயன்படுத்திய நாட்கள்',
        days_with_use: 'செயலி திறக்கப்பட்ட நாட்கள்',
        average_per_day: 'நாள் சராசரி',
        pages_this_month: 'இந்த மாதம் நாளொன்றுக்கு பக்கங்கள்',
        daily_usage: 'தினசரி பயன்பாடு',
        monthly_usage: 'மாதாந்திர பயன்பாடு',
        last_30_days: 'கடந்த 30 நாட்கள் · திறந்த பக்கங்கள்',
        last_12_months: 'கடந்த 12 மாதங்கள் · திறந்த பக்கங்கள்',
        who_used_recently: 'சமீபத்தில் யார் பயன்படுத்தினார்கள்',
        no_customer_usage: 'இதுவரை எந்த வாடிக்கையாளரும் பயன்படுத்தவில்லை. அவர்கள் உள்நுழைய முடிந்ததும் இங்கே தெரிவார்கள்.',
        no_usage_yet: 'இதுவரை எதுவும் பதிவாகவில்லை.',
        person_title: 'நபர்',
        last_seen: 'கடைசியாக பார்த்தது',
        last_page: 'கடைசி பக்கம்',
        days_used: 'பயன்படுத்திய நாட்கள்',
        staff_word: 'பணியாளர்',
        recent_activity: 'சமீபத்திய செயல்பாடு',
        time_word: 'நேரம்',
        page_word: 'பக்கம்',
        business_word: 'வணிகம்',
        device_word: 'சாதனம்',
        device_phone: 'கைபேசி',
        device_tablet: 'டேப்லெட்',
        device_computer: 'கணினி',
        ip_address: 'IP முகவரி',
        signed_in: 'உள்நுழைந்தார்',
        kept_a_month: 'திறந்த பக்கங்கள் ஒரு மாதம் வைக்கப்படும்',
        rice_sales_report: 'அரிசி விற்பனை',
        rice_sales_subtitle: 'எந்த அரிசி வேகமாக விற்கிறது — விற்ற மூட்டைகள், பங்கு, இந்த வேகத்தில் இருப்பு எத்தனை நாள் வரும்.',
        profit_loss: 'லாப நஷ்டம்',
        profit_subtitle: 'விற்பனை − விற்ற அரிசியின் கொள்முதல் விலை − செலவுகள் = பங்குதாரர்கள் பகிரும் லாபம்.',
        customer_dues: 'வாடிக்கையாளர் நிலுவை',
        customer_dues_subtitle: 'யார் செலுத்த வேண்டும், எவ்வளவு நாளாக நிலுவை. பெற்ற பணம் பழைய பில்களுக்கு முதலில் வரவு.',
        day_book: 'தினசரி கணக்கு',
        day_book_subtitle: 'ஒவ்வொரு நாளும்: விற்பனை, பெற்ற பணம், கொள்முதல், செலவு, நாள் முடிவில் மீதம்.',
        day_book_note: 'வரவு = அன்று வாடிக்கையாளர்கள் செலுத்தியது. செலவு = அரிசி கொள்முதல் + செலவுகள்.',
        traders_statement_subtitle: 'வாடிக்கையாளரைத் தேடி அனைத்து பில்கள், பணம் மற்றும் நிலுவையைப் பார்க்கவும் — அச்சிடலாம் அல்லது பதிவிறக்கலாம்.',
        speed_fast: 'வேகம்',
        speed_steady: 'சீராக',
        speed_slow: 'மெதுவாக',
        speed_none: 'விற்கவில்லை',
        speed_word: 'வேகம்',
        age_0_30: '0–30 நாட்கள்',
        age_31_60: '31–60 நாட்கள்',
        age_61_90: '61–90 நாட்கள்',
        age_over_90: '90 நாட்களுக்கு மேல்',
        everyone_owing: 'நிலுவை உள்ள அனைவரும்',
        older_30: '30 நாட்களுக்கு மேல் நிலுவை',
        older_60: '60 நாட்களுக்கு மேல் நிலுவை',
        older_90: '90 நாட்களுக்கு மேல் நிலுவை',
        show_word: 'காட்டு',
        oldest_unpaid: 'பழைய நிலுவை',
        last_paid: 'கடைசியாக செலுத்தியது',
        days_word: 'நாட்கள்',
        avg_rate_bag: 'சராசரி மூட்டை விலை',
        bags_a_day: 'மூட்டைகள் / நாள்',
        bags_per_day: 'மூட்டை / நாள்',
        bags_sold: 'விற்ற மூட்டைகள்',
        stock_lasts: 'இருப்பு போதும்',
        fastest_selling: 'வேகமாக விற்கும் அரிசி',
        top_customers: 'முன்னணி வாடிக்கையாளர்கள்',
        invoices_title: 'பில்கள்',
        cost_word: 'அடக்க விலை',
        profit_word: 'லாபம்',
        margin_word: 'லாப விகிதம்',
        gross_profit: 'மொத்த லாபம்',
        net_profit: 'நிகர லாபம்',
        less_cost_of_rice: 'கழிவு: விற்ற அரிசியின் அடக்க விலை',
        less_expenses: 'கழிவு: செலவுகள்',
        entries_word: 'பதிவுகள்',
        share_of: 'பங்கு —',
        profit_statement: 'லாப அறிக்கை',
        profit_by_rice: 'அரிசி வாரியாக லாபம்',
        money_view: 'குறிப்புக்கு',
        rice_purchased_period: 'இந்த காலத்தில் வாங்கிய அரிசி',
        purchases_note: 'இது முழுவதும் இன்னும் விற்கப்படவில்லை, எனவே மேலே உள்ள அடக்க விலை அல்ல.',
        stock_value: 'இப்போதுள்ள இருப்பு (அடக்க விலையில்)',
        missing_cost_note: 'கொள்முதல் விலை இல்லை:',
        set_prices: 'அரிசி வகைகளில் விலையை அமைக்கவும்',
        no_purchase_price_for: 'கொள்முதல் விலை அமைக்கப்படவில்லை:',
        profit_on_sale: 'இந்த விற்பனையில் லாபம்',
        purchase_price: 'கொள்முதல் விலை',
        selling_price: 'விற்பனை விலை',
        purchase_price_bag: 'கொள்முதல் விலை / மூட்டை (₹)',
        selling_price_bag: 'விற்பனை விலை / மூட்டை (₹)',
        profit_per_bag: 'லாபம் / மூட்டை',
        money_in: 'வரவு',
        net_cash: 'வரவு − செலவு',
        net_word: 'நிகரம்',
        purchases_word_title: 'கொள்முதல்',
        nothing_in_period: 'இந்த காலத்தில் எதுவும் பதிவு இல்லை',
        no_trading_customers: 'அரிசி வாங்கிய வாடிக்கையாளர் இல்லை.',
        recent_customers: 'சமீபத்தில் வாங்கிய வாடிக்கையாளர்கள்',
        date: 'தேதி',
        rate_per_bag: 'மூட்டை விலை',
        rate_per_bag_rupees: 'மூட்டை விலை (₹)',
        received_word: 'பெற்றது',
        new_purchase_subtitle: 'விற்பனையாளரிடம் வாங்கிய அரிசி. நீங்கள் பதிவதால் இருப்பு கூடும்.',
        new_sale_subtitle: 'வாடிக்கையாளருக்கு விற்ற அரிசி. இப்போது செலுத்தாத தொகை அவரது கடனில் சேரும்.',
        purchase_needs_masters: 'முதலில் குறைந்தது ஒரு அரிசி வகையும் ஒரு விற்பனையாளரும் சேர்க்கவும்.',
        sale_needs_varieties: 'முதலில் அரிசி வகைகளைச் சேர்க்கவும்.',
        supplier_word: 'விற்பனையாளர்',
        add_new_supplier: '+ புதிய விற்பனையாளரைச் சேர்',
        supplier_bill_no: 'விற்பனையாளர் பில் எண்',
        rice_bought: 'வாங்கிய அரிசி',
        rice_sold: 'விற்ற அரிசி',
        save_purchase: 'கொள்முதலைச் சேமி',
        customer_word: 'வாடிக்கையாளர்',
        type_to_find_customer: 'வாடிக்கையாளரைத் தேட தட்டச்சு செய்யவும்',
        already_owes: 'ஏற்கனவே தர வேண்டியது',
        add_new_customer: '+ புதிய வாடிக்கையாளரைச் சேர்',
        received_now: 'இப்போது பெற்றது',
        full_word: 'முழுவதும்',
        save_invoice: 'சேமித்து பில் பெறு',
        rice_variety: 'அரிசி வகை',
        bags_word: 'மூட்டைகள்',
        bags_lower: 'மூட்டைகள்',
        bag_kg: 'கிலோ / மூட்டை',
        rate_word: 'விலை (₹)',
        amount_word: 'தொகை',
        add_line: 'இன்னொரு அரிசி சேர்',
        total_word: 'மொத்தம்',
        bill_total: 'பில் மொத்தம்',
        customer_account: 'வாடிக்கையாளர் கணக்கு',
        delete_invoice: 'பில்லை நீக்கு',
        delete_purchase: 'கொள்முதலை நீக்கு',
        sales_subtitle: 'வாடிக்கையாளர்களுக்கு விற்ற அரிசி. இயல்பாக இந்த மாதம்.',
        purchases_subtitle: 'விற்பனையாளர்களிடம் வாங்கிய அரிசி. இயல்பாக இந்த மாதம்.',
        received_at_sale: 'விற்பனையின் போது பெற்றது',
        on_credit: 'கடனில்',
        paid_word: 'செலுத்தப்பட்டது',
        no_sales: 'இந்தக் காலத்தில் விற்பனை இல்லை',
        no_purchases: 'இந்தக் காலத்தில் கொள்முதல் இல்லை',
        purchases_word: 'கொள்முதல்கள்',
        stock_subtitle: 'கையிருப்பு அரிசி — வாங்கியது கழித்து விற்றது — தேதி',
        varieties_word: 'வகைகள்',
        no_varieties: 'இன்னும் அரிசி வகைகள் இல்லை',
        bought_bags: 'வாங்கியது (மூட்டை)',
        sold_bags: 'விற்றது (மூட்டை)',
        in_stock_bags: 'இருப்பு (மூட்டை)',
        kg_bag: 'கிலோ மூட்டை',
        balances_subtitle: 'ஒவ்வொரு வாடிக்கையாளரும் SN Traders-க்கு தர வேண்டியது — விற்பனை கழித்து பெற்ற பணம்.',
        customers_owing: 'தர வேண்டியவர்கள்',
        advances_word: 'முன்பணம்',
        customers_owe_total: 'வாடிக்கையாளர்கள் தர வேண்டியது',
        customers_word: 'வாடிக்கையாளர்கள்',
        customer_lower: 'வாடிக்கையாளர்',
        advances_held: 'வைத்துள்ள முன்பணம்',
        last_sale: 'கடைசி விற்பனை',
        sales_word: 'விற்பனை',
        advance_word: 'முன்பணம்',
        total_sales: 'மொத்த விற்பனை',
        total_received: 'மொத்தம் பெற்றது',
        balance_due: 'நிலுவைத் தொகை',
        entry_word: 'பதிவு',
        sale_word: 'விற்பனை',
        no_trades_yet: 'இன்னும் விற்பனையோ ரசீதோ இல்லை.',
        receive_payment_subtitle: 'அரிசி கடனுக்கு வாடிக்கையாளர் செலுத்தும் பணம்.',
        owes_word: 'தர வேண்டியது',
        nothing_owed: 'நிலுவை இல்லை',
        for_invoice: 'பில்லுக்கு',
        receipts_subtitle: 'வாடிக்கையாளர்களிடம் பெற்ற பணம் — விற்பனையின் போதும் பின்னரும். இயல்பாக இந்த மாதம்.',
        no_receipts: 'இந்தக் காலத்தில் ரசீதுகள் இல்லை',
        varieties_subtitle: 'நீங்கள் வாங்கி விற்கும் அரிசி — மூட்டை அளவு, மூட்டைக்கு கொள்முதல் மற்றும் விற்பனை விலையுடன். புதிய கொள்முதல் பில் கொள்முதல் விலையை மாற்றும்.',
        add_variety: 'வகையைச் சேர்',
        variety_name: 'பெயர்',
        add_word: 'சேர்',
        edit: 'திருத்து',
        inactive_word: 'செயலில் இல்லை',
        active_word: 'செயலில்',
        suppliers_subtitle: 'நீங்கள் அரிசி வாங்கும் ஆலைகள் மற்றும் மொத்த விற்பனையாளர்கள்.',
        add_supplier: 'விற்பனையாளரைச் சேர்',
        supplier_name: 'பெயர்',
        place_word: 'ஊர்',
        no_suppliers: 'இன்னும் விற்பனையாளர்கள் இல்லை',
        bought_word: 'வாங்கியது',
        menu_expenses: 'செலவுகள்',
        add_expense: 'செலவைச் சேர்',
        edit_expense: 'செலவைத் திருத்து',
        all_expenses: 'அனைத்து செலவுகள்',
        balance_sheet: 'இருப்புநிலைக் குறிப்பு',
        back_to_expenses: 'செலவுகளுக்குத் திரும்பு',
        expense_form_subtitle: 'ஒரு பங்குதாரர் செலுத்திய தொழில் செலவு. இது பங்குதாரர்களிடையே சமமாகப் பகிரப்படும்.',
        paid_by_partner: 'செலுத்தியவர்',
        expense_date: 'தேதி',
        spent_for: 'எதற்காக?',
        paid_to_whom: 'யாருக்குச் செலுத்தப்பட்டது (கடை / நபர்)',
        bill_number: 'பில் / குறிப்பு எண்',
        bill_short: 'பில்',
        save_and_add_another: 'சேமித்து இன்னொன்றைச் சேர்',
        save_expense: 'செலவைச் சேமி',
        delete_expense: 'இந்தச் செலவை நீக்கு',
        expenses_subtitle: 'பங்குதாரர்களின் தொழில் செலவுகள். இயல்பாக இந்த மாதம்.',
        both_partners: 'அனைத்து பங்குதாரர்கள்',
        expense_search_placeholder: 'எதற்காக, யாருக்கு, பில் எண்',
        expenses_word: 'செலவுகள்',
        no_expenses: 'இந்தக் காலத்தில் செலவுகள் இல்லை',
        range_year: 'இந்த ஆண்டு',
        range_all: 'எல்லா காலமும்',
        balance_sheet_subtitle: 'பங்குதாரர்களிடையே சமமாகப் பகிரப்படும் தொழில் செலவு, மற்றும் சமன் செய்ய யார் யாருக்குச் செலுத்த வேண்டும்.',
        balance_period_note: 'இந்த இருப்புகள் இந்தக் காலத்திற்கு மட்டுமே. தொடர் இருப்பிற்கு "எல்லா காலமும்" தேர்வு செய்யவும்.',
        total_spent: 'மொத்த செலவு',
        each_share: 'ஒவ்வொரு பங்குதாரரின் பங்கு',
        partners_word: 'பங்குதாரர்கள்',
        partners_heading: 'பங்குதாரர்கள்',
        partner_word: 'பங்குதாரர்',
        spent_paid: 'செலவுக்குச் செலுத்தியது',
        equal_share: 'சம பங்கு',
        settlement_given: 'கொடுத்த தீர்வுகள்',
        settlement_received: 'பெற்ற தீர்வுகள்',
        net_put_in: 'நிகர முதலீடு',
        to_receive: 'பெற வேண்டியது',
        to_pay: 'செலுத்த வேண்டியது',
        settled_word: 'சமன்',
        who_pays_whom: 'யார் யாருக்குச் செலுத்த வேண்டும்',
        pays_word: 'செலுத்த வேண்டியது',
        record_this_payment: 'இந்தக் கட்டணத்தைப் பதிவு செய்',
        all_square: 'அனைத்தும் சமன் — யாரும் யாருக்கும் கடன்படவில்லை.',
        record_settlement: 'தீர்வைப் பதிவு செய்',
        settlement_from: 'செலுத்தியவர்',
        settlement_to: 'பெற்றவர்',
        save_settlement: 'தீர்வைச் சேமி',
        settlements_heading: 'தீர்வுகள்',
        no_settlements: 'இந்தக் காலத்தில் தீர்வுகள் இல்லை.',
        pending_and_due: 'நிலுவை & செலுத்த வேண்டியவை',
        dues_report_subtitle: 'இன்னும் செலுத்த வேண்டியவர்கள், தேதி',
        pending_list_title: 'கெடு தேதி தாண்டியவை',
        due_list_title: 'இந்த மாதம் செலுத்த வேண்டியவை',
        nobody_on_list: 'இந்தப் பட்டியலில் யாரும் இல்லை.',
        due_date: 'கெடு தேதி',
        days_overdue: 'தாமத நாட்கள்',
        by_group: 'குழு வாரியாக',
        all_payments_subtitle: 'இன்றைய கட்டணங்கள். முந்தையவற்றைக் காண தேதிகள் மற்றும் வடிகட்டிகளைப் பயன்படுத்தவும்.',
        range_today: 'இன்று',
        range_yesterday: 'நேற்று',
        range_week: 'இந்த வாரம்',
        range_month: 'இந்த மாதம்',
        range_last_month: 'கடந்த மாதம்',
        from_date: 'இருந்து',
        to_date: 'வரை',
        back_to_today: 'இன்றைக்குத் திரும்பு',
        receipt_word: 'ரசீது',
        print_list: 'பட்டியலை அச்சிடு',
        no_payments_today: 'இன்று இன்னும் கட்டணம் இல்லை',
        no_payments_today_text: 'முந்தைய கட்டணங்களைக் காண மேலே வேறு தேதி அல்லது காலத்தைத் தேர்வு செய்யவும்.',
        prizes_won: 'வென்ற பரிசுகள்',
        open_full_form: 'முழுப் படிவத்தைத் திற',
        all_months_paid_short: 'அனைத்து மாதங்களும் செலுத்தப்பட்டன',
        nothing_paid_yet: 'இன்னும் எதுவும் செலுத்தவில்லை',
        paid_up_to: 'செலுத்தியது வரை',
        part_paid_lower: 'பகுதி செலுத்தியது',
        due_collections: 'செலுத்த வேண்டிய வசூல்',
        members_due: 'இந்த மாதம் செலுத்த வேண்டியவர்கள்',
        next_member: 'அடுத்த உறுப்பினர்',
        more_details: 'மேலும் விவரங்கள்',
        more_details_hint: 'தேதி & நேரம், குறிப்புகள்',
        upcoming_word: 'வரவிருக்கிறது',
        nothing_due: 'இப்போது செலுத்த வேண்டியவர்கள் இல்லை. மற்றவர்களைத் தேட தேடலைப் பயன்படுத்தவும்.',
        seats_due: 'செலுத்த வேண்டும் — மற்றவர்களைத் தேடவும்',
        all_months_paid: 'இந்தக் குழுவின் அனைத்து மாதங்களும் செலுத்தப்பட்டன.',
        choose_month: 'எந்த மாதத்திற்கான கட்டணம்?',
        due_on: 'கெடு',
        month_one_full_only: 'முதல் மாதம் முழுத் தொகையாக மட்டுமே — பகுதி கட்டணம் இல்லை.',
        continue_partial_month: 'இந்த மாதம் பகுதியாகச் செலுத்தப்பட்டுள்ளது. பகுதி கட்டணமாகத் தொடரவும்.',
        pay_full_month: 'முழு மாதம்',
        nothing_pending: 'இப்போது நிலுவையில் யாரும் இல்லை. மற்றவர்களைத் தேட தேடல் பெட்டியைப் பயன்படுத்தவும்.',
        payment_search_none_any: 'உங்கள் தேடலுக்கு உறுப்பினர்கள் இல்லை.',
        seats_pending: 'நிலுவை — மற்றவர்களைத் தேடவும்',
        seats_found: 'கண்டுபிடிக்கப்பட்டது',
        months_word: 'மாதங்கள்',
        customer_statement: 'வாடிக்கையாளர் அறிக்கை',
        customer_statement_subtitle: 'ஒவ்வொரு குழுவிலும், மாதவாரியாக, வாடிக்கையாளர் செலுத்திய ஒவ்வொரு கட்டணமும்.',
        find_another_customer: 'வேறு வாடிக்கையாளரைத் தேடு',
        print_statement: 'அறிக்கையை அச்சிடு',
        statement_search_placeholder: 'வாடிக்கையாளர் பெயர், எண், தொலைபேசி அல்லது அடையாளம்',
        statement_search_hint: 'வாடிக்கையாளரைக் கண்டுபிடிக்க பெயரைத் தட்டச்சு செய்யவும்.',
        statement_search_none: 'உங்கள் தேடலுக்கு எந்தக் குழுவிலும் வாடிக்கையாளர் இல்லை.',
        seats_word: 'இடம்(கள்)',
        address: 'முகவரி',
        total_paid: 'மொத்தம் செலுத்தியது',
        statement_no_groups: 'இந்த வாடிக்கையாளர் இன்னும் தொடங்கிய எந்தக் குழுவிலும் இல்லை.',
        no_payments_yet: 'இன்னும் கட்டணம் இல்லை'

    }

};


function applyTranslations(language) {

    const dictionary =
        window.SN_TRANSLATIONS[language] ||
        window.SN_TRANSLATIONS.en;


    document
        .querySelectorAll('[data-i18n]')
        .forEach(function (element) {

            const key = element.dataset.i18n;

            if (dictionary[key] !== undefined) {
                element.textContent = dictionary[key];
            }

        });


    document
        .querySelectorAll('[data-i18n-placeholder]')
        .forEach(function (element) {

            const key = element.dataset.i18nPlaceholder;

            if (dictionary[key] !== undefined) {
                element.placeholder = dictionary[key];
            }

        });

}


/*
|--------------------------------------------------------------------------
| LANGUAGE
|--------------------------------------------------------------------------
*/

window.changeLanguage = function (language) {

    localStorage.setItem(
        'sn-language',
        language
    );


    /*
    |--------------------------------------------------------------------------
    | Change translated text driven by the shared dictionary
    |--------------------------------------------------------------------------
    */

    applyTranslations(language);


    /*
    |--------------------------------------------------------------------------
    | Legacy per-element translations (data-en / data-ta pairs)
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '[data-en][data-ta]'
        )
        .forEach(function (element) {

            if (language === 'ta') {

                element.textContent =
                    element.dataset.ta;

            } else {

                element.textContent =
                    element.dataset.en;

            }

        });


    /*
    |--------------------------------------------------------------------------
    | Change placeholders (legacy data-placeholder-en / -ta pairs)
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '[data-placeholder-en][data-placeholder-ta]'
        )
        .forEach(function (element) {

            if (language === 'ta') {

                element.placeholder =
                    element.dataset.placeholderTa;

            } else {

                element.placeholder =
                    element.dataset.placeholderEn;

            }

        });


    /*
    |--------------------------------------------------------------------------
    | Header language buttons
    |--------------------------------------------------------------------------
    */

    const englishButton =
        document.getElementById(
            'englishButton'
        );

    const tamilButton =
        document.getElementById(
            'tamilButton'
        );


    if (englishButton) {

        englishButton.classList.toggle(
            'active',
            language === 'en'
        );

    }


    if (tamilButton) {

        tamilButton.classList.toggle(
            'active',
            language === 'ta'
        );

    }


    document.documentElement.lang =
        language === 'ta'
            ? 'ta'
            : 'en';

};


/*
|--------------------------------------------------------------------------
| LOAD SAVED SETTINGS
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | Theme
        |--------------------------------------------------------------------------
        */

        const savedTheme =
            localStorage.getItem(
                'sn-theme'
            );


        const theme =
            savedTheme === 'dark'
                ? 'dark'
                : 'light';


        document.documentElement.classList.remove(
            'dark',
            'light'
        );


        document.documentElement.classList.add(
            theme
        );


        /*
        |--------------------------------------------------------------------------
        | Language
        |--------------------------------------------------------------------------
        */

        const savedLanguage =
            localStorage.getItem(
                'sn-language'
            ) || 'en';


        window.changeLanguage(
            savedLanguage
        );


        /*
        |--------------------------------------------------------------------------
        | Close menu when clicking overlay
        |--------------------------------------------------------------------------
        */

        const overlay =
            document.getElementById(
                'menuOverlay'
            );


        if (overlay) {

            overlay.addEventListener(
                'click',
                function () {

                    window.toggleMenu();

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Close menu with Escape
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape'
                ) {

                    window.toggleAdminMenu(false);

                    const menu =
                        document.getElementById(
                            'appMenu'
                        );


                    if (
                        menu &&
                        menu.classList.contains(
                            'open'
                        )
                    ) {

                        window.toggleMenu();

                    }

                }

            }
        );

    }
);