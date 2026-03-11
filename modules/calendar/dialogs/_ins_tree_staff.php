<div class='ins-tree__branch'>
    <div class='ins-tree__node ins-tree__node--staff'>
        <span class='material-icons'>group</span>
        <div class='ins-tree__content'>
            <div class='ins-tree__label'>Проверяющие и задания</div>
            <table class='num_list ins-tree__table'>
                <tr>
                    <th>№</th>
                    <th>ФИО</th>
                    <th>Задача</th>
                    <th>Период</th>
                    <th>Статус</th>
                </tr>
                <?php $i = 1;
                foreach ($executors[$id] as $uid => $executor): ?>
                    <?php
                    $status = '<span class="greyText">Ожидает назначения</span>';
                    if (strlen($tasks_info[$uid]['dates']) > 0) {
                        $status = '<span class="blueText">Назначена</span>';
                        if ($tasks_info[$uid]['status'] == 1) {
                            $status = '<span class="greenText">Выполнена</span>';
                        }
                    }
                    ?>
                    <tr>
                        <td><?= $i ?>.</td>
                        <td>
                            <?= $executor ?>
                            <?php if ($tasks_info[$uid]['is_head']): ?>
                                <span class="greenText" title="Руководитель проверки">
                                    <span class="material-icons"
                                          style="font-size:14px;vertical-align:middle">star</span>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= $taskTemplates['array'][$tasks_info[$uid]['task']] ?></td>
                        <td><?= $date->periodToString($tasks_info[$uid]['dates']) ?></td>
                        <td><?= $status ?></td>
                    </tr>
                    <?php $i++; endforeach; ?>
            </table>
        </div>
    </div>
</div>