<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$page_title = 'Yearly Calendar';

$current_year = (int)($_GET['year'] ?? date('Y'));
$prev_year = $current_year - 1;
$next_year = $current_year + 1;

$holidays = db_get_all("SELECT * FROM holidays WHERE YEAR(date) = ? OR (is_recurring = 1 AND YEAR(date) <= ?) ORDER BY date ASC", [$current_year, $current_year]);

$holiday_map = [];
$month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

$type_colors = [
    'public' => ['bg' => 'bg-red-500', 'text' => 'text-red-600', 'light' => 'bg-red-50', 'badge' => 'bg-red-100 text-red-700'],
    'school' => ['bg' => 'bg-amber-500', 'text' => 'text-amber-600', 'light' => 'bg-amber-50', 'badge' => 'bg-amber-100 text-amber-700'],
    'event' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-600', 'light' => 'bg-blue-50', 'badge' => 'bg-blue-100 text-blue-700'],
];

// Build a map of date -> holidays, normalizing recurring years to the viewed year
foreach ($holidays as $h) {
    $parts = explode('-', $h['date']);
    $date_key = $h['is_recurring'] ? sprintf('%04d-%02d-%02d', $current_year, (int)$parts[1], (int)$parts[2]) : $h['date'];
    if (!isset($holiday_map[$date_key])) $holiday_map[$date_key] = [];
    $holiday_map[$date_key][] = $h;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                <i class="fas fa-calendar-alt text-teal-600 mr-2"></i> Yearly Calendar
            </h1>
            <p class="text-gray-500 text-sm mt-1">Monitor holidays and events for the academic year</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center bg-white border border-gray-200 rounded-lg shadow-sm">
                <a href="?year=<?= $prev_year ?>" class="px-3 py-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-l-lg transition border-r border-gray-200"><i class="fas fa-chevron-left text-xs"></i></a>
                <span class="px-4 py-2 font-bold text-gray-900 text-sm min-w-[80px] text-center"><?= $current_year ?></span>
                <a href="?year=<?= $next_year ?>" class="px-3 py-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-r-lg transition border-l border-gray-200"><i class="fas fa-chevron-right text-xs"></i></a>
            </div>
            <a href="?year=<?= date('Y') ?>" class="text-xs text-gray-500 hover:text-teal-600 <?= $current_year == date('Y') ? 'hidden' : '' ?>">Today</a>
            <?php if (has_role('admin', 'teacher')): ?>
            <a href="manage.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition flex items-center gap-2">
                <i class="fas fa-plus"></i> Manage Holidays
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-4 mb-6 px-1">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Legend:</span>
        <span class="flex items-center gap-1.5 text-xs text-gray-600"><span class="w-3 h-3 rounded-full bg-red-500"></span> Public Holiday</span>
        <span class="flex items-center gap-1.5 text-xs text-gray-600"><span class="w-3 h-3 rounded-full bg-amber-500"></span> School Holiday</span>
        <span class="flex items-center gap-1.5 text-xs text-gray-600"><span class="w-3 h-3 rounded-full bg-blue-500"></span> Event / Special Day</span>
        <span class="flex items-center gap-1.5 text-xs text-gray-400 ml-auto"><i class="far fa-calendar-check text-teal-500"></i> <?= count($holidays) ?> event(s) this year</span>
    </div>

    <!-- Yearly Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-5">
        <?php for ($m = 1; $m <= 12; $m++):
            $first_day = mktime(0, 0, 0, $m, 1, $current_year);
            $days_in_month = date('t', $first_day);
            $start_dow = (int)date('w', $first_day);
            $month_holidays = array_filter($holidays, function($h) use ($m, $current_year, $first_day, $days_in_month) {
                $hd = explode('-', $h['date']);
                $hm = (int)$hd[1];
                $hd_num = (int)$hd[2];
                $hy = (int)$hd[0];
                if ($h['is_recurring']) {
                    return $hm == $m;
                }
                return $hm == $m && $hy == $current_year;
            });
        ?>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition">
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-4 py-2.5 flex items-center justify-between">
                <h3 class="text-white font-bold text-sm"><?= $month_names[$m] ?></h3>
                <span class="text-xs text-white/70"><?= count($month_holidays) ?> events</span>
            </div>
            <div class="p-2">
                <div class="grid grid-cols-7 text-center">
                    <?php foreach ($day_names as $d): ?>
                    <div class="text-[10px] font-bold text-gray-400 uppercase py-1.5"><?= substr($d, 0, 1) ?></div>
                    <?php endforeach; ?>
                    <?php for ($i = 0; $i < $start_dow; $i++): ?>
                    <div></div>
                    <?php endfor; ?>
                    <?php for ($d = 1; $d <= $days_in_month; $d++):
                        $date_str = sprintf('%04d-%02d-%02d', $current_year, $m, $d);
                        $is_today = ($date_str == date('Y-m-d'));
                        $day_holidays = $holiday_map[$date_str] ?? [];
                        $is_holiday = !empty($day_holidays);
                        $holiday_type = $is_holiday ? $day_holidays[0]['type'] : null;
                        $holiday_title = $is_holiday ? $day_holidays[0]['title'] : null;

                        $cell_class = 'py-1 text-xs rounded-lg transition relative';
                        if ($is_today) $cell_class .= ' bg-teal-100 text-teal-800 font-bold';
                        elseif ($is_holiday) $cell_class .= ' ' . ($type_colors[$holiday_type]['light'] ?? '') . ' font-semibold';
                        else $cell_class .= ' text-gray-700 hover:bg-gray-50';
                    ?>
                    <div class="<?= $cell_class ?>" <?php if ($is_holiday): ?>title="<?= sanitize($holiday_title) ?>"<?php endif; ?>>
                        <span class="relative inline-flex items-center justify-center w-6 h-6">
                            <?= $d ?>
                            <?php if ($is_holiday): ?>
                            <span class="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 rounded-full <?= $type_colors[$holiday_type]['bg'] ?? 'bg-gray-400' ?>"></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php if (!empty($month_holidays)): ?>
            <div class="border-t border-gray-100 px-3 py-2 space-y-1 max-h-[100px] overflow-y-auto">
                <?php foreach (array_slice($month_holidays, 0, 3) as $mh):
                    $md = explode('-', $mh['date']);
                    $md_num = (int)$md[2];
                ?>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 <?= $type_colors[$mh['type']]['bg'] ?? 'bg-gray-400' ?>"></span>
                    <span class="font-medium text-gray-800 truncate"><?= sanitize($mh['title']) ?></span>
                    <span class="text-gray-400 ml-auto flex-shrink-0"><?= $md_num ?> <?= substr($month_names[$m], 0, 3) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (count($month_holidays) > 3): ?>
                <p class="text-[10px] text-gray-400 text-center">+<?= count($month_holidays) - 3 ?> more</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>

    <!-- Full Year Holiday List -->
    <?php if (!empty($holidays)): ?>
    <div class="mt-8 bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-list text-teal-600"></i> All Events in <?= $current_year ?>
            </h2>
        </div>
        <div class="overflow-x-auto -mx-4 sm:-mx-0">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Date</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Event</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden md:table-cell">Description</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider hidden sm:table-cell">Type</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Recurring</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sorted = $holidays;
                    usort($sorted, function($a, $b) {
                        $ad = $a['is_recurring'] ? substr_replace($a['date'], date('Y'), 0, 4) : $a['date'];
                        $bd = $b['is_recurring'] ? substr_replace($b['date'], date('Y'), 0, 4) : $b['date'];
                        return strcmp($ad, $bd);
                    });
                    ?>
                    <?php foreach ($sorted as $h):
                        $show_date = $h['is_recurring'] ? substr_replace($h['date'], $current_year, 0, 4) : $h['date'];
                    ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4">
                            <span class="font-medium text-gray-900"><?= format_date($show_date, 'd M Y') ?></span>
                            <?php if ($h['is_recurring']): ?>
                            <span class="text-[10px] text-teal-600 ml-1">(yearly)</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 font-semibold text-gray-900"><?= sanitize($h['title']) ?></td>
                        <td class="py-3 px-4 text-gray-500 hidden md:table-cell"><?= sanitize($h['description'] ?: '—') ?></td>
                        <td class="py-3 px-4 hidden sm:table-cell">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $type_colors[$h['type']]['badge'] ?? 'bg-gray-100 text-gray-600' ?>">
                                <?= ucfirst($h['type']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <?php if ($h['is_recurring']): ?>
                            <i class="fas fa-check-circle text-teal-500"></i>
                            <?php else: ?>
                            <span class="text-gray-300">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
