<style>
    body { font-family: sans-serif; font-size: 9px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 3px 5px; text-align: left; }
    th { background: #f1f1f1; font-weight: bold; }
    td.num { text-align: right; }
    h2 { margin: 0 0 6px; font-size: 13px; }
    .meta { margin-bottom: 8px; color: #555; font-size: 8px; }
</style>

<h2>Shipping Lines Export</h2>
<div class="meta">Generated {{ now()->format('d/m/Y H:i') }}</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Shipping Number</th>
        <th>Date Initiated</th>
        <th>Invoice Number</th>
        <th>Garden</th>
        <th>Grade</th>
        <th>Client Name</th>
        <th class="num">Packages</th>
        <th class="num">Weight</th>
        <th>Source</th>
        <th>Destination</th>
        <th>Status</th>
        <th class="num">Aging Days</th>
    </tr>
    </thead>
    <tbody>
