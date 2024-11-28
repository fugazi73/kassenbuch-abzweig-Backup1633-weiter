<table class="table table-hover">
    <thead>
        <tr>
            <th>Datum</th>
            <th>Beschreibung</th>
            <th>Betrag</th>
            <th>Typ</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($entries as $entry): ?>
        <tr data-entry-id="<?= $entry['id'] ?>">
            <td><?= htmlspecialchars(date('d.m.Y', strtotime($entry['date']))) ?></td>
            <td><?= htmlspecialchars($entry['description']) ?></td>
            <td class="<?= $entry['type'] === 'Ausgabe' ? 'text-danger' : 'text-success' ?>">
                <?= number_format($entry['amount'], 2, ',', '.') ?> €
            </td>
            <td><?= htmlspecialchars($entry['type']) ?></td>
            <td>
                <div class="btn-group">
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary edit-entry-btn" 
                            data-entry-id="<?= $entry['id'] ?>"
                            title="Bearbeiten">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger delete-entry-btn" 
                            data-entry-id="<?= $entry['id'] ?>"
                            title="Löschen">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table> 