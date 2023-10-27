
#From Vagrant
PATH="$HOME/.config/composer/vendor/bin:$PATH"

case "$TERM" in
xterm*|rxvt*)
    PROMPT_COMMAND='echo -ne "\033]0;${USER}@${HOSTNAME}: ${PWD/$HOME/~}\007"'
    ;;
*)
    ;;
esac

export TERM=xterm-256color
PROMPT_COLOR='0;1;37m'
PROMPT_COLOR2='38;5;68m'
PROMPT_COLOR3='1;34m'
PROMPT_DEFAULT='0;37m'
PS1='\e[${PROMPT_COLOR2}\h-\T\e[${PROMPT_COLOR}[\e[${PROMPT_COLOR3}\w\e[${PROMPT_COLOR}]$\e[${PROMPT_DEFAULT} '

#set my editor
export EDITOR=vim

#set language
export LC_ALL=en_US.utf-8
export LANG="$LC_ALL"

#set hist file stuff
export HISTSIZE=1000000
export HISTFILESIZE=1000000
export HISTTIMEFORMAT="%m/%d/%y %T "
