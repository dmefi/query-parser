<h1>Images</h1>
<?= $this->Html->link('Add image', ['action' => 'add'], array( 'class' => 'button')) ?>
<div class = 'main-container'>
    <?php foreach ($images as $image): ?>
        <div>
            <?= $this->Html->image('/upload/images/' . $image->filename, array('alt' => 'CakePHP')); ?>
            <p> <?= $image->filename ?>(<?= $image->width ?> x <?= $image->height ?>)</p>
        </div>
    <?php endforeach; ?>
</div>

<div class="pagination-wrapper">
    <ul class="pagination">
         <?php if($this->Paginator->total('Images') > 1): ?>
             <?= $this->Paginator->prev('< ' . __('previous')); ?>
             <?= $this->Paginator->numbers(); ?>
             <?= $this->Paginator->next(__('next') . ' >'); ?>
         <?php endif ?>
    </ul>
</div>
<div class="search">
    <div class="search-query">
        <?= $this->Form->input('Search query', ['row' => 2]) ?>
    </div>

    <div class="result-query">
        <?= $this->Form->input('Result query', ['row' => 2, 'disabled' => 'disabled']) ?>
    </div>
</div>
<?= $this->Form->button('Convert', ['class' => 'ajax-button']) ?>;

<? $this->append('script'); ?>
<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'></script>
<script>
    $(function() {
        $('.ajax-button').click(function(e) {
            $('#result-query').val('');
            var query = $('#search-query').val();
            console.log(query);
            $.ajax({
                type : "POST",
                url  : '<?= $this->Url->build(['controller' => 'Images','action' => 'parse']) ?>',
                data : {'query' : query },
                dataType: 'json',
                success: function(res) {
                    $('#result-query').val(JSON.stringify(res));
                },
                error: function(e) {
                    console.log(e);
                }
            });
        });
    });
</script>
<? $this->end(); ?>
