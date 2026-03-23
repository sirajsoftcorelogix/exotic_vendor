function loadPayments() {

    let params = new URLSearchParams({
        from_date: document.getElementById('from_date').value,
        to_date: document.getElementById('to_date').value,
        receipt_no: document.getElementById('receipt_no').value,
        order_number: document.getElementById('order_number').value,
        amount_min: document.getElementById('amount_min').value,
        amount_max: document.getElementById('amount_max').value,
        payment_mode: document.getElementById('payment_mode').value
    });

    fetch("?page=payments&action=list_ajax&" + params.toString())
        .then(res => res.json())
        .then(data => {

            let html = "";

            data.forEach(p => {

                html += `
                <tr class="border-t">
                    <td class="p-3">${p.id}</td>
                    <td class="p-3">${p.payment_date}</td>
                    <td class="p-3">${p.order_number}</td>
                    <td class="p-3">${p.warehouse}</td>
                    <td class="p-3 font-semibold">₹ ${p.amount}</td>
                    <td class="p-3">${p.payment_mode}</td>
                    <td class="p-3">${p.user}</td>
                    <td class="p-3">
                        <button class="text-blue-600">View</button>
                    </td>
                </tr>
                `;

            });

            document.getElementById("paymentTableBody").innerHTML = html;

        });

}

document.querySelectorAll("input,select").forEach(el => {
    el.addEventListener("change", loadPayments);
});

loadPayments();