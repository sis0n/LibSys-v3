<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Information System Report</title>
    <style>
        @page { margin: 100px 50px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        
        /* Header & Footer */
        .header { position: fixed; top: -80px; left: 0; right: 0; height: 80px; border-bottom: 2px solid #ea580c; }
        .footer { position: fixed; bottom: -60px; left: 0; right: 0; height: 40px; font-size: 9px; color: #666; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        .pagenum:before { content: counter(page); }

        /* Branding */
        .header-table { width: 100%; border: none; border-collapse: collapse; }
        .header-logo { width: 55px; height: 55px; }
        .header-text { text-align: center; vertical-align: middle; }
        .brand { color: #ea580c; font-size: 20px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .sub-brand { font-size: 12px; color: #666; margin: 0; }
        
        /* Layout */
        .report-info { margin-bottom: 20px; margin-top: 10px; }
        .report-title { font-size: 18px; font-weight: bold; color: #1f2937; margin-bottom: 5px; }
        .date-range { display: inline-block; background: #fff7ed; color: #9a3412; padding: 4px 12px; border-radius: 4px; font-weight: bold; }

        /* Summary Cards */
        .summary-grid { width: 100%; margin-bottom: 30px; }
        .summary-card { background: #fdfdfd; border: 1px solid #fed7aa; padding: 10px; text-align: center; width: 18%; }
        .summary-val { display: block; font-size: 18px; font-weight: bold; color: #ea580c; }
        .summary-label { display: block; font-size: 8px; color: #7c2d12; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; table-layout: fixed; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; text-transform: uppercase; font-size: 9px; padding: 10px 8px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; word-wrap: break-word; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bg-stripe { background-color: #fcfcfc; }
        .font-bold { font-weight: bold; }
        .row-total { background-color: #fff7ed; font-weight: bold; }

        /* Section Titles */
        h2 { font-size: 14px; color: #1e293b; margin-top: 0; margin-bottom: 12px; border-left: 4px solid #ea580c; padding-left: 10px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

    <?php
        function getBase64Image($path) {
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
            return '';
        }
        
        $libLogoPath = __DIR__ . '/../../../lib_logo.jpg';
        $uccLogoPath = __DIR__ . '/../../../ucc_logo.jpg';
        
        $libLogoBase64 = getBase64Image($libLogoPath);
        $uccLogoBase64 = getBase64Image($uccLogoPath);
    ?>

    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 65px; border: none; padding: 0;">
                    <?php if ($uccLogoBase64): ?>
                        <img src="<?= $uccLogoBase64 ?>" class="header-logo" alt="UCC Logo">
                    <?php endif; ?>
                </td>
                <td class="header-text" style="border: none; padding: 0;">
                    <p class="brand">University of Caloocan City</p>
                    <p class="sub-brand">UCC Library</p>
                </td>
                <td style="width: 65px; border: none; padding: 0; text-align: right;">
                    <?php if ($libLogoBase64): ?>
                        <img src="<?= $libLogoBase64 ?>" class="header-logo" alt="Library Logo">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Page <span class="pagenum"></span> | University of Caloocan City | UCC Library &copy; <?= date('Y') ?>
    </div>

    <div class="report-info">
        <div class="report-title">Library Performance Report</div>
        <div class="date-range">Coverage: <?= date('M d, Y', strtotime($startDate)) ?> &mdash; <?= date('M d, Y', strtotime($endDate)) ?></div>
    </div>

    <?php
    $totalDeleted = !empty($deletedBooks) ? ($deletedBooks[0]['range_total'] ?? 0) : 0;
    $totalCircBooks = 0;
    foreach(($circulatedBooks ?? []) as $row) if(($row['category'] ?? '') === 'TOTAL') $totalCircBooks = $row['range_total'] ?? 0;
    $totalCircEquip = 0;
    foreach(($circulatedEquipments ?? []) as $row) if(($row['category'] ?? '') === 'TOTAL') $totalCircEquip = $row['range_total'] ?? 0;
    $totalLostDamaged = 0;
    foreach(($lostDamagedBooks ?? []) as $row) if(($row['category'] ?? '') === 'TOTAL') $totalLostDamaged = $row['range_total'] ?? 0;
    $totalOverdue = 0;
    foreach(($overdueSummary ?? []) as $row) $totalOverdue += ($row['range_total'] ?? 0);
    ?>

    <table class="summary-grid">
        <tr>
            <td class="summary-card" style="border-left: 4px solid #ef4444;"><span class="summary-val"><?= number_format($totalDeleted) ?></span><span class="summary-label">Deleted</span></td>
            <td style="width: 1%; border: none;"></td>
            <td class="summary-card" style="border-left: 4px solid #3b82f6;"><span class="summary-val"><?= number_format($totalCircBooks) ?></span><span class="summary-label">Books</span></td>
            <td style="width: 1%; border: none;"></td>
            <td class="summary-card" style="border-left: 4px solid #10b981;"><span class="summary-val"><?= number_format($totalCircEquip) ?></span><span class="summary-label">Equip.</span></td>
            <td style="width: 1%; border: none;"></td>
            <td class="summary-card" style="border-left: 4px solid #f59e0b;"><span class="summary-val"><?= number_format($totalLostDamaged) ?></span><span class="summary-label">Lost/Damaged</span></td>
            <td style="width: 1%; border: none;"></td>
            <td class="summary-card" style="border-left: 4px solid #dc2626;"><span class="summary-val"><?= number_format($totalOverdue) ?></span><span class="summary-label">Overdue</span></td>
        </tr>
    </table>

    <div style="width: 100%;">
        <div style="width: 48%; float: left;">
            <h2>Circulated Books</h2>
            <table>
                <thead><tr><th style="width: 60%;">User Category</th><th class="text-center">Count</th></tr></thead>
                <tbody>
                    <?php foreach (($circulatedBooks ?? []) as $row): ?>
                    <tr class="<?= ($row['category'] ?? '') === 'TOTAL' ? 'row-total' : '' ?>">
                        <td><?= htmlspecialchars($row['category'] ?? 'Unknown') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['range_total'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="width: 48%; float: right;">
            <h2>Circulated Equipments</h2>
            <table>
                <thead><tr><th style="width: 60%;">User Category</th><th class="text-center">Count</th></tr></thead>
                <tbody>
                    <?php foreach (($circulatedEquipments ?? []) as $row): ?>
                    <tr class="<?= ($row['category'] ?? '') === 'TOTAL' ? 'row-total' : '' ?>">
                        <td><?= htmlspecialchars($row['category'] ?? 'Unknown') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['range_total'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div style="width: 100%;">
        <div style="width: 48%; float: left;">
            <h2>Lost & Damaged Books</h2>
            <table>
                <thead><tr><th style="width: 60%;">Status</th><th class="text-center">Count</th></tr></thead>
                <tbody>
                    <?php foreach (($lostDamagedBooks ?? []) as $row): ?>
                    <tr class="<?= ($row['category'] ?? '') === 'TOTAL' ? 'row-total' : '' ?>">
                        <td><?= htmlspecialchars($row['category'] ?? 'Unknown') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['range_total'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="width: 48%; float: right;">
            <h2>Library Resources</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 60%;">Type</th>
                        <th class="text-center">Active Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Total Books</td><td class="text-center"><?= number_format($libraryResources['available_books'] ?? 0) ?> (Avail.)</td></tr>
                    <tr><td>Total Equipments</td><td class="text-center"><?= number_format($libraryResources['total_equipments'] ?? 0) ?></td></tr>
                    <tr class="row-total"><td>Total Collection</td><td class="text-center"><?= number_format($libraryResources['total_collection'] ?? 0) ?></td></tr>
                </tbody>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div style="width: 100%;">
        <div style="width: 48%; float: left;">
            <h2>Overdue Summary</h2>
            <table>
                <thead><tr><th style="width: 60%;">Type</th><th class="text-center">Count</th></tr></thead>
                <tbody>
                    <?php foreach (($overdueSummary ?? []) as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['category'] ?? 'Unknown') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['range_total'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="row-total"><td>Total Overdue</td><td class="text-center"><?= $totalOverdue ?></td></tr>
                </tbody>
            </table>
        </div>
        <div style="width: 48%; float: right;">
            <p style="font-size: 9px; color: #666; font-style: italic; margin-top: 20px;">
                Note: "Active Count" represents current system totals, while other figures are specific to the selected date range.
            </p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="page-break"></div>

    <h2>Most Borrowed Books (Top 10)</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">Rank</th>
                <th style="width: 50%;">Book Title</th>
                <th style="width: 27%;">Accession No.</th>
                <th style="width: 15%;" class="text-center">Borrows</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mostBorrowedBooks)): ?>
                <tr><td colspan="4" class="text-center">No data available.</td></tr>
            <?php else: ?>
                <?php foreach ($mostBorrowedBooks as $index => $book): ?>
                <tr class="<?= $index % 2 === 0 ? '' : 'bg-stripe' ?>">
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td class="font-bold"><?= htmlspecialchars($book['title']) ?><br><small style="color: #666; font-weight: normal;">by <?= htmlspecialchars($book['author']) ?></small></td>
                    <td><?= htmlspecialchars($book['accession_number']) ?></td>
                    <td class="text-center font-bold" style="color: #3b82f6;"><?= htmlspecialchars($book['range_total']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Top Borrowers (Top 10)</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">Rank</th>
                <th style="width: 40%;">Name</th>
                <th style="width: 22%;">ID / Username</th>
                <th style="width: 15%;">Role</th>
                <th style="width: 15%;" class="text-center">Borrows</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topBorrowers)): ?>
                <tr><td colspan="5" class="text-center">No data available.</td></tr>
            <?php else: ?>
                <?php foreach ($topBorrowers as $index => $b): ?>
                <tr class="<?= $index % 2 === 0 ? '' : 'bg-stripe' ?>">
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td class="font-bold"><?= htmlspecialchars($b['full_name']) ?></td>
                    <td><?= htmlspecialchars($b['identifier']) ?></td>
                    <td style="text-transform: capitalize;"><?= htmlspecialchars($b['role']) ?></td>
                    <td class="text-center font-bold" style="color: #ea580c;"><?= htmlspecialchars($b['range_total']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Top 10 Most Active Visitors (Attendance)</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">Rank</th>
                <th style="width: 40%;">Name</th>
                <th style="width: 22%;">ID Number</th>
                <th style="width: 15%;">Course / Dept</th>
                <th style="width: 15%;" class="text-center">Visits</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topVisitors)): ?>
                <tr><td colspan="5" class="text-center">No data available.</td></tr>
            <?php else: ?>
                <?php foreach ($topVisitors as $index => $v): ?>
                <tr class="<?= $index % 2 === 0 ? '' : 'bg-stripe' ?>">
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td class="font-bold"><?= htmlspecialchars($v['full_name']) ?></td>
                    <td><?= htmlspecialchars($v['student_number']) ?></td>
                    <td><?= htmlspecialchars($v['course']) ?></td>
                    <td class="text-center font-bold" style="color: #ea580c;"><?= htmlspecialchars($v['visits']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 10px; color: #666;">
        <p><strong>Note:</strong> This report was automatically generated on <?= date('F j, Y g:i A') ?>. All data is based on the UCC Library Information System records for the specified coverage period.</p>
    </div>

</body>
</html>