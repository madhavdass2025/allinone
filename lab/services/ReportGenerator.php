<?php
interface ReportStrategy {
    public function render($order, $results);
}

class DefaultReportStrategy implements ReportStrategy {
    public function render($order, $results) {
        ob_start();
        ?>
        <div class="report-container">
            <div class="row mb-5 border-bottom pb-3">
                <div class="col-6">
                    <h2 class="text-primary fw-bold">PET CLINICAL LAB</h2>
                    <p class="mb-0">123 Vet Clinic Ave, Paws City<br>Phone: +91 999 888 7777</p>
                </div>
                <div class="col-6 text-end">
                    <h4 class="text-muted">LABORATORY REPORT</h4>
                    <p class="mb-0">Order ID: #<?php echo $order['id']; ?><br>Date: <?php echo date('d/m/Y', strtotime($order['order_date'])); ?></p>
                </div>
            </div>

            <div class="row mb-4 bg-light p-3 rounded">
                <div class="col-md-4">
                    <strong>Owner Name:</strong> <?php echo $order['owner_name']; ?>
                </div>
                <div class="col-md-4 text-center">
                    <strong>Pet Name:</strong> <?php echo $order['pet_name']; ?> (<?php echo $order['species']; ?>)
                </div>
                <div class="col-md-4 text-end">
                    <strong>Age:</strong> <?php echo $order['age_years']; ?>Y <?php echo $order['age_months']; ?>M
                </div>
            </div>

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Investigation</th>
                        <th>Observed Value</th>
                        <th>Reference Range</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($results as $res):
                        $ranges = json_decode($res['reference_ranges'], true);
                        $ref = $ranges[$order['species']] ?? ['min' => '-', 'max' => '-'];
                        $flag_class = ($res['flag'] != 'Normal') ? 'text-danger fw-bold' : '';
                    ?>
                    <tr>
                        <td><?php echo $res['parameter_name']; ?></td>
                        <td class="<?php echo $flag_class; ?>">
                            <?php echo $res['result_value']; ?>
                            <?php if ($res['flag'] != 'Normal') echo "({$res['flag']})"; ?>
                        </td>
                        <td><?php echo $ref['min'] . ' - ' . $ref['max']; ?></td>
                        <td><?php echo $res['unit']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row mt-5">
                <div class="col-12">
                    <p><strong>Clinical Observations:</strong> Normal findings for the specified age group.</p>
                </div>
                <div class="col-12 text-end mt-5">
                    <div class="d-inline-block text-center" style="width: 200px;">
                        <div class="border-bottom mb-2" style="height: 50px;"></div>
                        <p class="mb-0 small"><strong>Dr. Sarah Jenkins</strong></p>
                        <p class="small text-muted">Consultant Pathologist</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

class ReportGenerator {
    private $strategy;

    public function __construct(ReportStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public function generate($order, $results) {
        return $this->strategy->render($order, $results);
    }
}
?>
