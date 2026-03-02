<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

ob_start();

?>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Dashboard
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Central Hub for Personal Customization
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed pb-7.5">
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure obcaecati nesciunt dicta temporibus eaque, similique voluptatum cumque quis aut nisi reiciendis recusandae optio ad voluptatem, illo sed! Perferendis, debitis minus?
</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/admin/app.php';