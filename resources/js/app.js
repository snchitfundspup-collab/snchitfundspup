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
        collect_payment_subtitle: 'Pick a member who still owes, then record a full month or any partial amount.',
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
        ledger_due: 'Due',
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
        ledger_won: 'Won the draw that month',
        won_word: 'Won'

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
        collect_payment_subtitle: 'நிலுவை உள்ள உறுப்பினரைத் தேர்ந்தெடுத்து, முழு மாதம் அல்லது பகுதி தொகையைப் பதிவு செய்யவும்.',
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
        ledger_won: 'அந்த மாதம் குலுக்கலில் வென்றார்',
        won_word: 'வென்றார்'

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