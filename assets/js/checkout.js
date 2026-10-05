/* Checkout: shows the price for the quantity while it is typed. The server still works out the real bill. */
(function () {
    var data = document.getElementById('co-slabs');
    var input = document.getElementById('quantity');
    var note = document.getElementById('co-estimate');
    var total = document.getElementById('co-total');
    if (!data || !input) { return; }
    var slabs;
    try { slabs = JSON.parse(data.textContent); } catch (e) { return; }
    function money(n) { return 'NPR ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function show() {
        var qty = parseInt(String(input.value).replace(/[^\d]/g, ''), 10);
        if (!qty) {
            if (note) { note.textContent = ''; }
            if (total) { total.textContent = 'Set the quantity'; }
            return;
        }
        var slab = null;
        slabs.forEach(function (s) { if (qty >= s.min && qty <= s.max) { slab = s; } });
        if (!slab) {
            var low = slabs[0].min, high = slabs[slabs.length - 1].max;
            if (note) { note.textContent = 'Choose between ' + low.toLocaleString('en-US') + ' and ' + high.toLocaleString('en-US') + '.'; }
            if (total) { total.textContent = '—'; }
            return;
        }
        var net = Math.round(slab.unit * qty * 100) / 100;
        var bill = Math.round(net * 1.13 * 100) / 100;
        if (note) { note.textContent = qty.toLocaleString('en-US') + ' at ' + money(slab.unit) + ' each = ' + money(net) + ', about ' + money(bill) + ' with 13% VAT.'; }
        if (total) { total.textContent = 'about ' + money(bill); }
        Array.prototype.forEach.call(document.querySelectorAll('#co-ladder tbody tr'), function (row, i) {
            row.className = slabs[i] === slab ? 'is-current' : '';
        });
    }
    input.addEventListener('input', show);
    show();
})();
