# Meme generator

## Installation

```
composer require jelle-s/meme-generator
```

## Documentation

```
Usage:
  meme:generate [options] [--] <image> <top-text> [<bottom-text>]
  MemeGenerator\Command\GenerateMemeCommand

Arguments:
  image                            Image to put the text on.
  top-text                         Text to add on the top.
  bottom-text                      Text to add on the bottom (optional).

Options:
  -o, --output-file[=OUTPUT-FILE]  The name of the output file [default: "meme.jpeg"]
  -h, --help                       Display this help message
```

## Example

```
vendor/bin/meme-generator base_file.jpeg "This is the top text" "This is the optional bottom text" -o result.jpeg
```
