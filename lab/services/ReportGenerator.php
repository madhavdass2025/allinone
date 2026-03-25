<?php
interface ReportStrategy {
    public function render($order, $results);
}

class DefaultReportStrategy implements ReportStrategy {
    protected function renderHeader($order) { ?>
        <div class="row mb-4 border-bottom pb-3">
            <div class="col-6">
                <h2 class="text-primary fw-bold">PET CLINICAL LAB</h2>
                <p class="mb-0 small">123 Vet Clinic Ave, Paws City | Phone: +91 999 888 7777</p>
            </div>
            <div class="col-6 text-end">
                <h4 class="text-muted">LABORATORY REPORT</h4>
                <p class="mb-0 small">Order ID: #<?php echo $order['id']; ?><br>Date: <?php echo date('d/m/Y', strtotime($order['order_date'])); ?></p>
            </div>
        </div>

        <div class="row mb-4 bg-light p-3 rounded border">
            <div class="col-md-4"><strong>Owner:</strong> <?php echo $order['owner_name']; ?></div>
            <div class="col-md-4 text-center"><strong>Pet:</strong> <?php echo $order['pet_name']; ?> (<?php echo $order['species']; ?>)</div>
            <div class="col-md-4 text-end"><strong>Age:</strong> <?php echo $order['age_years']; ?>Y <?php echo $order['age_months']; ?>M</div>
        </div>
    <?php }

    protected function renderFooter() { ?>
        <div class="row mt-5">
            <div class="col-12"><p class="small border-top pt-2"><em>This is a computer-generated report and does not require a physical signature.</em></p></div>
            <div class="col-12 text-end mt-4">
                <div class="d-inline-block text-center" style="width: 200px;">
                    <div class="border-bottom mb-2" style="height: 40px;"></div>
                    <p class="mb-0 small"><strong>Dr. Sarah Jenkins</strong></p>
                    <p class="small text-muted">Consultant Pathologist</p>
                </div>
            </div>
        </div>
    <?php }

    public function render($order, $results) {
        ob_start();
        echo '<div class="report-container">';
        $this->renderHeader($order);
        ?>
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr><th>Investigation</th><th>Result</th><th>Ref. Range</th><th>Unit</th></tr>
            </thead>
            <tbody>
                <?php foreach($results as $res):
                    $ranges = json_decode($res['reference_ranges'], true);
                    $ref = $ranges[$order['species']] ?? ['min' => '-', 'max' => '-'];
                ?>
                <tr>
                    <td><?php echo $res['parameter_name']; ?></td>
                    <td class="<?php echo ($res['flag'] != 'Normal') ? 'text-danger fw-bold' : ''; ?>">
                        <?php echo $res['result_value']; ?> <?php if ($res['flag'] != 'Normal') echo "({$res['flag']})"; ?>
                    </td>
                    <td><?php echo $ref['min'] . ' - ' . $ref['max']; ?></td>
                    <td><?php echo $res['unit']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $this->renderFooter();
        echo '</div>';
        return ob_get_clean();
    }
}

class HematologyReportStrategy extends DefaultReportStrategy {
    public function render($order, $results) {
        ob_start();
        echo '<div class="report-container hematology-template">';
        $this->renderHeader($order);
        echo '<h5 class="text-center fw-bold mb-3 text-uppercase" style="letter-spacing: 2px;">Department of Hematology</h5>';
        ?>
        <div class="border rounded p-3 mb-4 bg-white">
            <table class="table table-borderless table-sm mb-0">
                <thead class="border-bottom">
                    <tr><th>PARAMETER</th><th class="text-center">OBSERVED VALUE</th><th class="text-center">REFERENCE RANGE</th><th class="text-center">UNIT</th></tr>
                </thead>
                <tbody>
                    <?php foreach($results as $res):
                        $ranges = json_decode($res['reference_ranges'], true);
                        $ref = $ranges[$order['species']] ?? ['min' => '-', 'max' => '-'];
                    ?>
                    <tr class="border-bottom-dashed">
                        <td class="fw-bold"><?php echo $res['parameter_name']; ?></td>
                        <td class="text-center <?php echo ($res['flag'] != 'Normal') ? 'text-danger h5' : 'text-primary'; ?>">
                            <?php echo $res['result_value']; ?>
                        </td>
                        <td class="text-center small text-muted"><?php echo $ref['min']; ?> - <?php echo $ref['max']; ?></td>
                        <td class="text-center small"><?php echo $res['unit']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-info py-2 small">Hematology analysis performed using automated cell counter with peripheral smear correlation.</div>
        <?php
        $this->renderFooter();
        echo '</div>';
        return ob_get_clean();
    }
}

class ImagingReportStrategy extends DefaultReportStrategy {
    public function render($order, $results) {
        ob_start();
        echo '<div class="report-container imaging-template">';
        $this->renderHeader($order);
        echo '<h5 class="text-center fw-bold mb-4 text-decoration-underline">RADIOLOGY / IMAGING REPORT</h5>';

        foreach($results as $res): ?>
            <div class="mb-5">
                <h6 class="fw-bold text-primary"><?php echo $res['parameter_name']; ?>:</h6>
                <div class="p-3 border rounded bg-white shadow-sm" style="min-height: 200px; white-space: pre-wrap; font-style: italic;">
                    <?php echo $res['result_value']; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="row mt-4">
            <div class="col-12">
                <p class="fw-bold mb-1">IMPRESSION:</p>
                <div class="p-2 border-start border-4 border-primary">Clinical correlation is advised for final diagnosis.</div>
            </div>
        </div>
        <?php
        $this->renderFooter();
        echo '</div>';
        return ob_get_clean();
    }
}

class ReportGenerator {
    public static function getStrategy($strategy_name) {
        switch (strtolower($strategy_name)) {
            case 'hematology':
                return new HematologyReportStrategy();
            case 'imaging':
            case 'radiology':
                return new ImagingReportStrategy();
            case 'default':
            default:
                return new DefaultReportStrategy();
        }
    }

    private $strategy;

    public function __construct(ReportStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public function generate($order, $results) {
        return $this->strategy->render($order, $results);
    }
}
?>
