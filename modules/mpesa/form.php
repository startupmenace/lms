<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>
   <style>
    @import url('https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap');
    *{ font-family: "Geist", sans-serif; }
</style>
<form action="./simulate.php" method="post">
<div class="w-full bg-black py-20 px-6 flex items-center justify-center">
    <div class="w-full max-w-[1110px] grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-20">
        
        <!-- Left Side -->
        <div class="flex flex-col justify-start pt-1">
            <div class="flex items-center gap-2.5 mb-6">
                <div class="size-2 rounded-full bg-orange-500"></div>
                <span class="text-white font-medium text-sm tracking-wide">MAKE PAYMENT WITH MPESA</span>
            </div>
            <h1 class="text-4xl font-medium text-white mb-3 sm:mb-5">We’re Here to Help You</h1>
            <p class="text-base text-zinc-500 leading-relaxed max-w-[420px]">
                Make instant payments with mpesa api to continue enjoying our services. Fill the form to get started with your payment.
            </p>
        </div>

        <!-- Right Side -->
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-2.5">
                <label class="text-white text-sm">amount</label>
                <input type="text" name="amount" placeholder="1" class="w-full px-3.5 py-2.5 rounded-sm bg-zinc-950 border border-zinc-800 text-white placeholder:text-zinc-600 text-sm focus:outline-none focus:border-zinc-600 transition-colors" />
            </div>
            
            <div class="flex flex-col gap-2.5">
                <label class="text-white text-sm">Phone number</label>
                <input type="text" name="phone_number" placeholder="0712345678" class="w-full px-3.5 py-2.5 rounded-sm bg-zinc-950 border border-zinc-800 text-white placeholder:text-zinc-600 text-sm focus:outline-none focus:border-zinc-600 transition-colors" />
            </div>

            <div class="flex flex-col gap-2.5">
                <label class="text-white text-sm">Message</label>
                <textarea placeholder="Hello, I have a query regarding your platform…" rows="6" class="w-full px-3.5 py-3 rounded-sm bg-zinc-950 border border-zinc-800 text-white placeholder:text-zinc-600 text-sm focus:outline-none focus:border-zinc-600 transition-colors resize-none"></textarea>
            </div>
  
            <button type="submit" name="submit" class="w-full mt-1 bg-orange-600 hover:bg-orange-500 text-white text-sm font-medium py-3 rounded-sm transition-colors cursor-pointer">
                Make payment today 
            </button>
        </div>
    </div>
</div>
</form>
</body>
</html>