\name{leap.event.add}
\alias{leap.event.add}
\title{
    Events
}
\description{
    Adding events.
}
\usage{
    leap.event.add(name, fun)
}
\arguments{
    \item{name}{
        Event name
    }
    \item{fun}{
        Function
    }
}
\details{
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
    leap.event.add("onTemplateSubmit", function(response) {
        print(response)
    })
}

\keyword{events}
