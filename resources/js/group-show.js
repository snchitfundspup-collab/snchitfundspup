/*
|--------------------------------------------------------------------------
| VIEW GROUP — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| Member "Details" popup: full customer details + every group the
| customer is in. Data comes from the #membersData JSON the page embeds.
*/

document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('memberModal');

    if (!modal) {
        return;
    }


    const members = JSON.parse(
        document.getElementById('membersData')?.textContent || '{}'
    );

    const AVATAR_TONES = ['orange', 'blue', 'purple'];

    const STATUS_LABELS = {
        forming: 'Forming',
        running: 'Running',
        completed: 'Completed'
    };

    let lastFocused = null;


    function setText(id, value) {

        document.getElementById(id).textContent =
            value || '—';

    }


    function fillGroups(groups) {

        const list = document.getElementById('memberDetailGroups');

        list.innerHTML = '';

        groups.forEach(function (group) {

            const item = document.createElement('li');

            const link = document.createElement('a');
            link.href = group.url;
            link.className = 'member-group-link';

            const name = document.createElement('strong');
            name.textContent = group.name;

            const code = document.createElement('span');
            code.className = 'member-group-code';
            code.textContent = group.member_code;

            const status = document.createElement('span');
            status.className = 'group-status group-status-' + group.status;
            status.textContent = STATUS_LABELS[group.status] || group.status;

            link.append(name, code, status);
            item.appendChild(link);
            list.appendChild(item);

        });

    }


    function openModal(memberId, trigger) {

        const member = members[memberId];

        if (!member) {
            return;
        }

        lastFocused = trigger;


        /* avatar: same initial + tone rule as <x-customer-avatar> */

        const avatar = document.getElementById('memberModalAvatar');

        AVATAR_TONES.forEach(function (tone) {
            avatar.classList.remove('icon-3d-' + tone);
        });

        avatar.classList.add(
            'icon-3d-' + AVATAR_TONES[member.customer_id % AVATAR_TONES.length]
        );

        avatar.textContent =
            (member.name || '?').trim().charAt(0).toUpperCase();


        setText('memberModalCode', member.member_code);
        setText('memberModalName', member.name);
        setText('memberDetailCustomerCode', member.customer_code);
        setText('memberDetailMemberCode', member.member_code);
        setText('memberDetailEmail', member.email);
        setText('memberDetailRemarks', member.remarks);
        setText('memberDetailAddress', member.address);
        setText('memberDetailStatus', member.is_active ? 'Active' : 'Inactive');

        setText('memberDetailPhone', member.phone);

        const callButton = document.getElementById('memberDetailCall');
        const dialNumber = String(member.phone || '').replace(/[^\d+]/g, '');

        callButton.hidden = dialNumber === '';
        callButton.href = 'tel:' + dialNumber;
        callButton.setAttribute('aria-label', 'Call ' + member.name + ' on ' + member.phone);

        fillGroups(member.groups || []);


        modal.hidden = false;

        document.body.classList.add('modal-open');

        modal.querySelector('.member-modal-close').focus();

    }


    function closeModal() {

        modal.hidden = true;

        document.body.classList.remove('modal-open');

        if (lastFocused) {
            lastFocused.focus();
        }

    }


    document.addEventListener('click', function (event) {

        const button = event.target.closest('.member-details-button');

        if (button) {
            openModal(button.dataset.memberId, button);
            return;
        }

        if (event.target.closest('[data-close-member-modal]')) {
            closeModal();
        }

    });


    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }

    });

});
