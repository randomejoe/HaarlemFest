<div class="row max-width vertical-center">
    <div class="search-bar-container">
        <div class="row space-between vertical-center">
            <form method="GET" action="/cms/users" id="search-form">
                <input id="search-input" name="search" placeholder="Search users..." value=<?= $params["search"] ?? ""?>>
                <i class="bi bi-search"></i>
            </form>
        </div>
        
    </div>
    <label for="sort">Sort by:</label>
    <?php 
        $param = "sort_by";
        $options = [
            'Newest' => 'date_desc', 
            'Oldest' => 'date_asc', 
            'Name (A-Z)' => 'name_asc', 
            'Name (Z-A)' => 'name_desc'
        ]; 
        $hasNone = false;
        
        $currentOption = 'Oldest';

        foreach ($options as $label => $info) {
            if (($params['sort_by'] ?? null) === $info) {
                $currentOption = $label;
                break;
            }
        }
        require __DIR__ . '/selector.php';
    ?>
</div>

<script>
    document.getElementById('search-form').addEventListener('submit', e => {
        e.preventDefault();

        const search = document.getElementById('search-input').value;

        updateParams('search', search);
    });
</script>
