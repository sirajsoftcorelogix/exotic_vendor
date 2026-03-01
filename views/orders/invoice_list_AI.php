<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Listing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #ddd;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        tbody tr:hover {
            background-color: #f9f9f9;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status.paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status.overdue {
            background-color: #f8d7da;
            color: #721c24;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .actions button {
            padding: 6px 12px;
            font-size: 12px;
        }

        .selected-count {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Invoice Listing</h1>

        <div class="controls">
            <div class="search-box">
                <input type="text" placeholder="Search invoices...">
            </div>
            <button>Export Selected</button>
            <button>Delete Selected</button>
            <span class="selected-count">0 selected</span>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" class="checkbox" id="selectAll"></th>
                        <th>Invoice ID</th>
                        <th>Vendor</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="checkbox" class="checkbox"></td>
                        <td>INV-001</td>
                        <td>Vendor A</td>
                        <td>$1,500.00</td>
                        <td>2024-01-15</td>
                        <td>2024-02-15</td>
                        <td><span class="status paid">Paid</span></td>
                        <td><div class="actions"><button>View</button><button>Edit</button></div></td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="checkbox"></td>
                        <td>INV-002</td>
                        <td>Vendor B</td>
                        <td>$2,300.00</td>
                        <td>2024-01-20</td>
                        <td>2024-02-20</td>
                        <td><span class="status pending">Pending</span></td>
                        <td><div class="actions"><button>View</button><button>Edit</button></div></td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="checkbox"></td>
                        <td>INV-003</td>
                        <td>Vendor C</td>
                        <td>$900.00</td>
                        <td>2023-12-20</td>
                        <td>2024-01-20</td>
                        <td><span class="status overdue">Overdue</span></td>
                        <td><div class="actions"><button>View</button><button>Edit</button></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>