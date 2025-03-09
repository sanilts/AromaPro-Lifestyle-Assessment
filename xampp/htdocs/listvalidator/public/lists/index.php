<?php
/**
 * Lists overview page
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/list_management.php';

// Require authentication
require_auth();

$user_id = $_SESSION['user_id'];
$is_admin = is_admin();

// Handle search
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Get lists with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Admin can see all lists, regular users only see their own
if ($is_admin && isset($_GET['all']) && $_GET['all'] == 1) {
    $lists = get_all_lists(null, $limit, $offset);
    $total_lists = count_all_lists();
} else {
    $lists = get_all_lists($user_id, $limit, $offset);
    $total_lists = count_lists_by_user($user_id);
}

$total_pages = ceil($total_lists / $limit);

// Helper function for this page
function count_all_lists() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) as total FROM lists";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

$page_title = 'My Lists - ' . APP_NAME;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
</head>
<body>
    <?php include_once VIEW_PATH . '/layout/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include_once VIEW_PATH . '/layout/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php echo $is_admin && isset($_GET['all']) && $_GET['all'] == 1 ? 'All Lists' : 'My Lists'; ?>
                        </h5>
                        <div>
                            <?php if ($is_admin): ?>
                                <a href="<?php echo BASE_URL; ?>/lists/index.php<?php echo isset($_GET['all']) && $_GET['all'] == 1 ? '' : '?all=1'; ?>" class="btn btn-sm btn-light mr-1">
                                    <i class="fas fa-<?php echo isset($_GET['all']) && $_GET['all'] == 1 ? 'user' : 'users'; ?> mr-1"></i>
                                    <?php echo isset($_GET['all']) && $_GET['all'] == 1 ? 'My Lists' : 'All Lists'; ?>
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>/lists/create.php" class="btn btn-sm btn-light">
                                <i class="fas fa-plus mr-1"></i> New List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php display_flash_messages(); ?>
                        
                        <form method="get" action="" class="mb-4">
                            <?php if ($is_admin && isset($_GET['all']) && $_GET['all'] == 1): ?>
                                <input type="hidden" name="all" value="1">
                            <?php endif; ?>
                            
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search lists..." value="<?php echo htmlspecialchars($search); ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if (empty($lists)): ?>
                            <div class="alert alert-info">
                                No lists found. <a href="<?php echo BASE_URL; ?>/lists/create.php">Create your first list</a>.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>List Name</th>
                                            <?php if ($is_admin && isset($_GET['all']) && $_GET['all'] == 1): ?>
                                                <th>Owner</th>
                                            <?php endif; ?>
                                            <th>Contacts</th>
                                            <th>Valid</th>
                                            <th>Invalid</th>
                                            <th>Downloads</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lists as $list): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($list['list_name']); ?></td>
                                                <?php if ($is_admin && isset($_GET['all']) && $_GET['all'] == 1): ?>
                                                    <td><?php echo htmlspecialchars($list['created_by']); ?></td>
                                                <?php endif; ?>
                                                <td><?php echo htmlspecialchars($list['contact_count']); ?></td>
                                                <td>
                                                    <?php 
                                                    $valid_count = isset($list['valid_count']) ? $list['valid_count'] : 0;
                                                    echo $valid_count;
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $invalid_count = isset($list['invalid_count']) ? $list['invalid_count'] : 0;
                                                    echo $invalid_count;
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($list['download_count']); ?></td>
                                                <td><?php echo format_datetime($list['created_at'], 'M d, Y'); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list['id']; ?>" class="btn btn-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>/lists/download.php?id=<?php echo $list['id']; ?>" class="btn btn-success" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <?php if ($is_admin || $list['user_id'] == $user_id): ?>
                                                            <a href="<?php echo BASE_URL; ?>/lists/delete.php?id=<?php echo $list['id']; ?>" class="btn btn-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this list?');" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $is_admin && isset($_GET['all']) && $_GET['all'] == 1 ? '&all=1' : ''; ?>">Previous</a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">Previous</span>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $is_admin && isset($_GET['all']) && $_GET['all'] == 1 ? '&all=1' : ''; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $is_admin && isset($_GET['all']) && $_GET['all'] == 1 ? '&all=1' : ''; ?>">Next</a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">Next</span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>