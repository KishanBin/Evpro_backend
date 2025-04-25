<!DOCTYPE html>
<html>
<head>
    <title>{{ $orderData['title'] }}</title>
</head>
<body>
    <h1>EVpro</h1>
    <p>Your booking has been {{ $orderData['message'] }} successfully.</p>
    <p>Order Details:</p>
    <ul>
        <li>Station: {{ $orderData['station_name'] }}</li>
        <li>Address: {{ $orderData['station_location'] }}</li>
        <li>Port No: {{ $orderData['station_port'] }}</li>
        <li>Time Slot: {{ $orderData['time_slot'] }}</li>
        <li>Booking Of: {{ $orderData['booking_of'] }}</li>
        <!-- Add more order details as needed -->
    </ul>
</body>
</html>