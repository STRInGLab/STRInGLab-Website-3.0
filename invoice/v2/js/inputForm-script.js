function addRow() {
    const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();

    for (let i = 0; i < 5; i++) {
        let cell = newRow.insertCell(i);
        let input;

        if (i !== 4) {
            input = document.createElement('input');
            input.type = (i === 1 || i === 2 || i === 3) ? 'number' : 'text';
            input.name = (i === 0) ? 'description[]' : (i === 1) ? 'qty[]' : (i === 2) ? 'price[]' : 'discount[]';
        }

        if (i === 4) {
            input = document.createElement('input');
            input.type = 'text';
            input.readOnly = true;
        }

        cell.appendChild(input);
    }
}