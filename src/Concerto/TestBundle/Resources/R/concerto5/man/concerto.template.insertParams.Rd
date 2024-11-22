\name{leap.template.insertParams}
\alias{leap.template.insertParams}
\title{
    Template helper function.
}
\description{
    Fills template HTML with given parameters.
}
\usage{
    leap.template.insertParams(html, params = list(), removeMissing=T)
}
\arguments{
    \item{html}{
        HTML template.
    }
    \item{params}{
        Named list with parameters.
    }
    \item{removeMissing}{
        Should missing insert params be cleared?
    }
}
\details{
}
\value{
    HTML with inserted parameters.
}
\references{
}
\author{
    Kooky Whiz
}
\note{
}

\seealso{
}
\examples{
    html <- "<html><body>Inserted variable: {{param1}}</body></html>"
    html <- leap.template.insertParams(html,list(param1="value"))
}

\keyword{template}
\keyword{HTML}
