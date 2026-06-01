<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_module_access('transport');

$page_title = 'Transport Management';
$tab = $_GET['tab'] ?? 'overview';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_vehicle') {
        db_insert("INSERT INTO transport_vehicles (vehicle_number,vehicle_type,capacity,model,year,fuel_type,insurance_expiry,last_maintenance,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)", [
            $_POST['vehicle_number'], $_POST['vehicle_type'], (int)$_POST['capacity'],
            $_POST['model'] ?? null, (int)($_POST['year'] ?? 0) ?: null,
            $_POST['fuel_type'] ?? 'diesel',
            $_POST['insurance_expiry'] ?: null, $_POST['last_maintenance'] ?: null,
            $_POST['status'] ?? 'active', $_POST['notes'] ?? null, get_user_id()
        ]);
        set_flash('success', 'Vehicle added successfully');
        redirect('?tab=vehicles');
    }
    if ($action === 'edit_vehicle') {
        db_query("UPDATE transport_vehicles SET vehicle_number=?,vehicle_type=?,capacity=?,model=?,year=?,fuel_type=?,insurance_expiry=?,last_maintenance=?,status=?,notes=? WHERE id=?", [
            $_POST['vehicle_number'], $_POST['vehicle_type'], (int)$_POST['capacity'],
            $_POST['model'] ?? null, (int)($_POST['year'] ?? 0) ?: null,
            $_POST['fuel_type'] ?? 'diesel',
            $_POST['insurance_expiry'] ?: null, $_POST['last_maintenance'] ?: null,
            $_POST['status'] ?? 'active', $_POST['notes'] ?? null, (int)$_POST['id']
        ]);
        set_flash('success', 'Vehicle updated');
        redirect('?tab=vehicles');
    }
    if ($action === 'delete_vehicle') {
        db_query("DELETE FROM transport_vehicles WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Vehicle deleted');
        redirect('?tab=vehicles');
    }

    if ($action === 'add_driver') {
        db_insert("INSERT INTO transport_drivers (name,phone,email,license_number,license_expiry,date_of_birth,address,emergency_contact_name,emergency_contact_phone,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)", [
            $_POST['name'], $_POST['phone'] ?? null, $_POST['email'] ?? null,
            $_POST['license_number'] ?? null, $_POST['license_expiry'] ?: null,
            $_POST['date_of_birth'] ?: null, $_POST['address'] ?? null,
            $_POST['emergency_contact_name'] ?? null, $_POST['emergency_contact_phone'] ?? null,
            $_POST['status'] ?? 'active', $_POST['notes'] ?? null, get_user_id()
        ]);
        set_flash('success', 'Driver added');
        redirect('?tab=drivers');
    }
    if ($action === 'edit_driver') {
        db_query("UPDATE transport_drivers SET name=?,phone=?,email=?,license_number=?,license_expiry=?,date_of_birth=?,address=?,emergency_contact_name=?,emergency_contact_phone=?,status=?,notes=? WHERE id=?", [
            $_POST['name'], $_POST['phone'] ?? null, $_POST['email'] ?? null,
            $_POST['license_number'] ?? null, $_POST['license_expiry'] ?: null,
            $_POST['date_of_birth'] ?: null, $_POST['address'] ?? null,
            $_POST['emergency_contact_name'] ?? null, $_POST['emergency_contact_phone'] ?? null,
            $_POST['status'] ?? 'active', $_POST['notes'] ?? null, (int)$_POST['id']
        ]);
        set_flash('success', 'Driver updated');
        redirect('?tab=drivers');
    }
    if ($action === 'delete_driver') {
        db_query("DELETE FROM transport_drivers WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Driver deleted');
        redirect('?tab=drivers');
    }

    if ($action === 'add_route') {
        $route_id = db_insert("INSERT INTO transport_routes (name,vehicle_id,driver_id,start_point,end_point,departure_time,arrival_time,fee_amount,description,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)", [
            $_POST['name'], (int)($_POST['vehicle_id'] ?? 0) ?: null, (int)($_POST['driver_id'] ?? 0) ?: null,
            $_POST['start_point'] ?? null, $_POST['end_point'] ?? null,
            $_POST['departure_time'] ?: null, $_POST['arrival_time'] ?: null,
            (float)($_POST['fee_amount'] ?? 0), $_POST['description'] ?? null,
            $_POST['status'] ?? 'active', get_user_id()
        ]);
        $stops = json_decode($_POST['stops'] ?? '[]', true) ?: [];
        foreach ($stops as $i => $stop) {
            if (!empty(trim($stop['name'] ?? ''))) {
                db_insert("INSERT INTO transport_route_stops (route_id,name,landmark,latitude,longitude,stop_order,pickup_time,drop_time) VALUES (?,?,?,?,?,?,?,?)", [
                    $route_id, $stop['name'], $stop['landmark'] ?? null,
                    $stop['latitude'] ? (float)$stop['latitude'] : null,
                    $stop['longitude'] ? (float)$stop['longitude'] : null,
                    $i + 1, $stop['pickup_time'] ?: null, $stop['drop_time'] ?: null
                ]);
            }
        }
        set_flash('success', 'Route created');
        redirect('?tab=routes');
    }
    if ($action === 'edit_route') {
        $id = (int)$_POST['id'];
        db_query("UPDATE transport_routes SET name=?,vehicle_id=?,driver_id=?,start_point=?,end_point=?,departure_time=?,arrival_time=?,fee_amount=?,description=?,status=? WHERE id=?", [
            $_POST['name'], (int)($_POST['vehicle_id'] ?? 0) ?: null, (int)($_POST['driver_id'] ?? 0) ?: null,
            $_POST['start_point'] ?? null, $_POST['end_point'] ?? null,
            $_POST['departure_time'] ?: null, $_POST['arrival_time'] ?: null,
            (float)($_POST['fee_amount'] ?? 0), $_POST['description'] ?? null,
            $_POST['status'] ?? 'active', $id
        ]);
        db_query("DELETE FROM transport_route_stops WHERE route_id=?", [$id]);
        $stops = json_decode($_POST['stops'] ?? '[]', true) ?: [];
        foreach ($stops as $i => $stop) {
            if (!empty(trim($stop['name'] ?? ''))) {
                db_insert("INSERT INTO transport_route_stops (route_id,name,landmark,latitude,longitude,stop_order,pickup_time,drop_time) VALUES (?,?,?,?,?,?,?,?)", [
                    $id, $stop['name'], $stop['landmark'] ?? null,
                    $stop['latitude'] ? (float)$stop['latitude'] : null,
                    $stop['longitude'] ? (float)$stop['longitude'] : null,
                    $i + 1, $stop['pickup_time'] ?: null, $stop['drop_time'] ?: null
                ]);
            }
        }
        set_flash('success', 'Route updated');
        redirect('?tab=routes');
    }
    if ($action === 'delete_route') {
        db_query("DELETE FROM transport_routes WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Route deleted');
        redirect('?tab=routes');
    }

    if ($action === 'assign_student') {
        $exists = db_get_row("SELECT id FROM transport_route_students WHERE route_id=? AND student_id=?", [(int)$_POST['route_id'], (int)$_POST['student_id']]);
        if ($exists) {
            set_flash('error', 'Student already assigned');
        } else {
            db_insert("INSERT INTO transport_route_students (route_id,student_id,stop_id,fee_amount,session) VALUES (?,?,?,?,?)", [
                (int)$_POST['route_id'], (int)$_POST['student_id'],
                (int)($_POST['stop_id'] ?? 0) ?: null,
                (float)($_POST['fee_amount'] ?? 0), $_POST['session'] ?? null
            ]);
            set_flash('success', 'Student assigned');
        }
        redirect('?tab=assign');
    }
    if ($action === 'unassign_student') {
        db_query("DELETE FROM transport_route_students WHERE id=?", [(int)$_POST['id']]);
        set_flash('success', 'Student removed');
        redirect('?tab=assign');
    }

    if ($action === 'mark_attendance') {
        $date = $_POST['date'];
        $route_id = (int)$_POST['route_id'];
        $trip_type = $_POST['trip_type'] ?? 'pickup';
        $students = $_POST['students'] ?? [];
        $remarks_list = $_POST['remarks'] ?? [];

        foreach ($students as $student_id => $status) {
            $student_id = (int)$student_id;
            $remarks = sanitize($remarks_list[$student_id] ?? '');
            $existing = db_get_row("SELECT id FROM transport_attendance WHERE route_id=? AND student_id=? AND date=? AND trip_type=?", [$route_id, $student_id, $date, $trip_type]);
            if ($existing) {
                db_query("UPDATE transport_attendance SET status=?,remarks=?,marked_by=? WHERE id=?", [$status, $remarks, get_user_id(), $existing['id']]);
            } else {
                db_insert("INSERT INTO transport_attendance (route_id,student_id,date,status,trip_type,remarks,marked_by) VALUES (?,?,?,?,?,?,?)", [$route_id, $student_id, $date, $status, $trip_type, $remarks, get_user_id()]);
            }
        }
        set_flash('success', 'Attendance saved for ' . format_date($date));
        redirect('?tab=attendance');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                <i class="fas fa-bus text-teal-600 mr-2"></i> Transport Management
            </h1>
            <p class="text-gray-500 text-sm mt-1">Manage school fleet, routes, drivers and student transport</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="?tab=overview" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab == 'overview' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-th-large mr-2"></i>Overview
            </a>
            <a href="?tab=vehicles" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab == 'vehicles' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-truck mr-2"></i>Vehicles
            </a>
            <a href="?tab=drivers" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab == 'drivers' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-id-card mr-2"></i>Drivers
            </a>
            <a href="?tab=routes" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab == 'routes' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-road mr-2"></i>Routes
            </a>
            <a href="?tab=assign" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab == 'assign' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-user-plus mr-2"></i>Assign Students
            </a>
            <a href="?tab=attendance" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab == 'attendance' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-clipboard-check mr-2"></i>Attendance
            </a>
            <a href="?tab=reports" class="px-5 py-3 text-sm font-medium whitespace-nowrap <?= $tab == 'reports' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">
                <i class="fas fa-chart-bar mr-2"></i>Reports
            </a>
        </div>

        <div class="p-5">
            <?php
            $tab_file = __DIR__ . '/_' . $tab . '.php';
            if (file_exists($tab_file)) {
                include $tab_file;
            } else {
                echo '<p class="text-gray-500 text-center py-8">Tab not found.</p>';
            }
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
