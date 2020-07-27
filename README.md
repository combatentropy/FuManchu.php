# Phu Manchu
The worst things about Mustache combined with PHP.

Phu Manchu is a template language for PHP.
It's meant to be fast and lightweight.
Right now it is less than 100 lines.

Like Mustache it is meant to keep templates simple,
but unlike Mustache it does not deny that it has some logic.
For all templates must have at least branching and loops.
And indeed Mustache does.
But Phu Manchu, taking from Handlebars, makes it more obvious.
That is, it uses words like "if" and "each" instead of punctuation like "#"
to mark those statements.
I think this works better because you hear what you write as you write it.

But I have also tried to make it less than even Mustache.
It uses single braces instead of double.
The syntax is so limited,
I don't think the patterns would accidentally match the wrong thing.

```html
{+ head.html}
<p>Hi, my name is {name}.</p>
{if excited}
    <p>I'm excited!</p>
{elseif sad}
    <p>I'm feeling a little sad.</p>
{else}
    <p>I'm just kind of blah.</p>
{/if}

{if travels}
    <p>Here are the places I have been:</p>
    <ul>
    {each travels as t}
        <li>In {t.year} I went to {t.place}.</li>
    {/each}
    </ul>
{else}
    <p>I've never been anywhere.</p>
{/if}
{+ foot.html}

```

That is the entire syntax.
If you want more you have to prep your data more.
There is no _not_ operator, like `!`.
Make the boolean for your if-statement positive.
It's clearer that way and not much more work.

When looping with `each`, there is no access to the key.
Sorry, restructure your data.

You can have the template in a string literal, like a heredoc, or a file.
Let's say it's in a file called **hi.html**. Here is all you have to do.

```php
<?

require 'ph_m.php';

$data = [
    'name' => 'Arthur',
    'excited' => true,
    'sad' => false,
    'travels' => [
        [
            'year' => 1983,
            'place' => 'Zimbabwe'
        ],
        [
            'year' => 1987,
            'place' => 'Bangledesh'
        ],
        [
            'year' => 1992,
            'place' => 'Brazil'
        ]
    ]
];

$template = 'hi.html';

ph_m($data, $template);
```

`$template` can be a string or file name. Don't worry, it will figure it out.

Phu Manchu is deliberately limited to keep templates from changing data.
Yes, it goes beyond even that, to keep it simple, lightweight, and clear.
