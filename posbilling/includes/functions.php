<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function getPagination($total_records, $limit, $page, $url) {
    $total_pages = ceil($total_records / $limit);
    if ($total_pages <= 1) return '';

    $html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-end mb-0 no-print">';

    $prev_disabled = ($page <= 1) ? 'disabled' : '';
    $html .= "<li class='page-item $prev_disabled'><a class='page-link' href='{$url}&page=" . ($page - 1) . "'>Previous</a></li>";

    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($page == $i) ? 'active' : '';
        $html .= "<li class='page-item $active'><a class='page-link' href='{$url}&page=$i'>$i</a></li>";
    }

    $next_disabled = ($page >= $total_pages) ? 'disabled' : '';
    $html .= "<li class='page-item $next_disabled'><a class='page-link' href='{$url}&page=" . ($page + 1) . "'>Next</a></li>";
    $html .= '</ul></nav>';

    return $html;
}

function flashMessage($name = '', $message = '', $class = 'alert alert-success') {
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            if (!empty($_SESSION[$name])) {
                unset($_SESSION[$name]);
            }
            if (!empty($_SESSION[$name . '_class'])) {
                unset($_SESSION[$name . '_class']);
            }
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}
?>
