#import "typst_templates/uni.typ": *
#show: uni_style

#set heading(numbering: "1.1")
#let section_head(body, numbering: true) = {
  align(
    center,
    heading(
      numbering: if numbering { "1" } else { none },
      body
    )
  )
  v(2.5em)
}


#set page(numbering: "1", number-align: top + center)

#section_head(numbering: false)[АННОТАЦИЯ]



#pagebreak()
#section_head(numbering: false)[СОДЕРЖАНИЕ]

#outline(title: none)

#pagebreak()
#section_head(numbering: false)[ВВЕДЕНИЕ]



#pagebreak()
#section_head()[ПОСТАНОВКА ЗАДАЧИ]

#pagebreak()
#section_head()[АНАЛИЗ И ОПИСАНИЕ ПРЕДМЕТНОЙ ОБЛАСТИ]

#pagebreak()
#section_head()[ПРОЕКТИРОВАНИЕ БД ВЕБ-САЙТА]

#pagebreak()
#section_head()[ВЫБОР ИНСТРУМЕНТАЛЬНЫХ СРЕДСТВ]

#pagebreak()
#section_head()[РАЗРАБОТКА РАЗДЕЛА АДМИНИСТРАТОРА]

== Проектирование интерфейса раздела администратора.

== Разработка программных модулей раздела администратора.

#pagebreak()
#section_head()[РАЗРАБОТКА РАЗДЕЛА ПОЛЬЗОВАТЕЛЯ]

== Проектирование интерфейса раздела пользователя.

== Разработка программных модулей раздела пользователя.

#pagebreak()
#section_head()[ТЕСТИРОВАНИЕ РАЗРАБОТАННОГО САЙТА]

== Анализ кроссбраузерности сайта.

== Профилирование разработанного сайта.

== Тестовые примеры работы.
