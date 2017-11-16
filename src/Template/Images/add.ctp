<h1>Add Image</h1>
<?php
    echo $this->Form->create($image, ['type' => 'file']);
    echo $this->Form->file('upload');
    echo $this->Form->input('width');
    echo $this->Form->input('height');
    echo $this->Form->input('top', array( 'type' => 'number', 'required' => true ));
    echo $this->Form->input('left', array( 'type' => 'number', 'required' => true ));
    echo $this->Form->button(__('Save image'));
    echo $this->Form->end();
?>